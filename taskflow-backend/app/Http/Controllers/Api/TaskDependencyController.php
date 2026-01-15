<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskDependency;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskDependencyController extends Controller
{
    /**
     * Listar dependencias de una tarea
     * GET /api/v1/tasks/{taskId}/dependencies
     */
    public function index($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        $dependencies = TaskDependency::where('task_id', $taskId)
            ->with('dependsOnTask')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dependencies,
        ], 200);
    }

    /**
     * Crear una nueva dependencia
     * POST /api/v1/tasks/{taskId}/dependencies
     */
    public function store(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        $validated = $request->validate([
            'depends_on_task_id' => 'required|exists:tasks,id',
            'dependency_type' => 'required|in:FS,SS,FF,SF',
            'lag_days' => 'nullable|integer',
        ]);

        // Verificar que no sea la misma tarea
        if ($taskId == $validated['depends_on_task_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Una tarea no puede depender de sí misma',
            ], 400);
        }

        // Verificar que no exista ya esta dependencia
        $exists = TaskDependency::where('task_id', $taskId)
            ->where('depends_on_task_id', $validated['depends_on_task_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Esta dependencia ya existe',
            ], 400);
        }

        // Crear la dependencia
        $dependency = TaskDependency::create([
            'task_id' => $taskId,
            'depends_on_task_id' => $validated['depends_on_task_id'],
            'dependency_type' => $validated['dependency_type'],
            'lag_days' => $validated['lag_days'] ?? 0,
        ]);

        // Verificar si la tarea debe bloquearse
        $this->updateTaskBlockStatus($taskId);

        return response()->json([
            'success' => true,
            'message' => 'Dependencia creada exitosamente',
            'data' => $dependency->load('dependsOnTask'),
        ], 201);
    }

    /**
     * Eliminar una dependencia
     * DELETE /api/v1/dependencies/{id}
     */
    public function destroy($id)
    {
        $dependency = TaskDependency::findOrFail($id);
        $taskId = $dependency->task_id;
        
        $dependency->delete();

        // Actualizar el estado de bloqueo
        $this->updateTaskBlockStatus($taskId);

        return response()->json([
            'success' => true,
            'message' => 'Dependencia eliminada exitosamente',
        ], 200);
    }

    /**
     * Verificar si una tarea está bloqueada por sus dependencias
     * GET /api/v1/tasks/{taskId}/check-blocked
     */
    public function checkBlocked($taskId)
    {
        $task = Task::findOrFail($taskId);
        $isBlocked = $this->isTaskBlocked($task);

        return response()->json([
            'success' => true,
            'is_blocked' => $isBlocked,
            'blocked_by' => $isBlocked ? $this->getBlockingTasks($task) : [],
        ], 200);
    }

    /**
     * Lógica para verificar si una tarea está bloqueada
     */
    private function isTaskBlocked(Task $task)
    {
        // Si ya está completada, no está bloqueada
        if ($task->status === 'completed') {
            return false;
        }

        // Verificar dependencias
        $dependencies = TaskDependency::where('task_id', $task->id)
            ->with('dependsOnTask')
            ->get();

        foreach ($dependencies as $dependency) {
            $dependsOn = $dependency->dependsOnTask;

            if (!$dependsOn) continue;

            // Lógica según el tipo de dependencia
            switch ($dependency->dependency_type) {
                case 'FS': // Finish-to-Start
                    if ($dependsOn->status !== 'completed') {
                        return true;
                    }
                    break;

                case 'SS': // Start-to-Start
                    if ($dependsOn->status === 'pending') {
                        return true;
                    }
                    break;

                case 'FF': // Finish-to-Finish
                    // Esta tarea no puede terminar hasta que la otra termine
                    if ($dependsOn->status !== 'completed' && $task->status === 'in_progress') {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    /**
     * Obtener las tareas que están bloqueando
     */
    private function getBlockingTasks(Task $task)
    {
        $blockingTasks = [];
        $dependencies = TaskDependency::where('task_id', $task->id)
            ->with('dependsOnTask')
            ->get();

        foreach ($dependencies as $dependency) {
            $dependsOn = $dependency->dependsOnTask;
            if (!$dependsOn) continue;

            $isBlocking = false;
            $reason = '';

            switch ($dependency->dependency_type) {
                case 'FS':
                    if ($dependsOn->status !== 'completed') {
                        $isBlocking = true;
                        $reason = "Esperando que '{$dependsOn->title}' se complete";
                    }
                    break;

                case 'SS':
                    if ($dependsOn->status === 'pending') {
                        $isBlocking = true;
                        $reason = "Esperando que '{$dependsOn->title}' inicie";
                    }
                    break;

                case 'FF':
                    if ($dependsOn->status !== 'completed') {
                        $isBlocking = true;
                        $reason = "Debe terminar junto con '{$dependsOn->title}'";
                    }
                    break;
            }

            if ($isBlocking) {
                $blockingTasks[] = [
                    'task_id' => $dependsOn->id,
                    'task_title' => $dependsOn->title,
                    'dependency_type' => $dependency->dependency_type,
                    'reason' => $reason,
                ];
            }
        }

        return $blockingTasks;
    }

    /**
     * Actualizar el estado de bloqueo de una tarea
     */
    private function updateTaskBlockStatus($taskId)
    {
        $task = Task::find($taskId);
        if (!$task) return;

        $isBlocked = $this->isTaskBlocked($task);

        if ($isBlocked && $task->status !== 'blocked' && $task->status !== 'completed') {
            $blockingTasks = $this->getBlockingTasks($task);
            $reasons = array_column($blockingTasks, 'reason');
            
            $task->update([
                'status' => 'blocked',
                'blocked_reason' => implode('. ', $reasons),
            ]);
        } elseif (!$isBlocked && $task->status === 'blocked') {
            $task->update([
                'status' => 'pending',
                'blocked_reason' => null,
            ]);
        }
    }
}