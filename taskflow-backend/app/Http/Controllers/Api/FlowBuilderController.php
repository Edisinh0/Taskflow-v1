<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flow;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Flow Builder Controller
 *
 * Controlador exclusivo para el módulo Flow Builder.
 * Solo accesible por PM/Administradores.
 *
 * Responsabilidades:
 * - Diseño de flujos
 * - Creación de estructura de tareas
 * - Configuración de dependencias
 * - Gestión de milestones
 */
class FlowBuilderController extends Controller
{


    /**
     * Crear nuevo flujo
     * POST /api/v1/flow-builder/flows
     */
    public function createFlow(Request $request)
    {
        // Autorización mediante Policy
        Gate::authorize('create', Flow::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_id' => 'nullable|exists:templates,id',
            'status' => 'nullable|in:active,paused,completed,cancelled',
        ]);

        $flow = Flow::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flujo creado exitosamente',
            'data' => $flow->load(['template', 'creator']),
        ], 201);
    }

    /**
     * Actualizar estructura del flujo
     * PUT /api/v1/flow-builder/flows/{id}
     */
    public function updateFlow(Request $request, $id)
    {
        $flow = Flow::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('update', $flow);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,paused,completed,cancelled',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed' && !$flow->completed_at) {
            $validated['completed_at'] = now();
        }

        $flow->update([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Flujo actualizado exitosamente',
            'data' => $flow->load(['template', 'creator', 'lastEditor']),
        ], 200);
    }

    /**
     * Eliminar flujo
     * DELETE /api/v1/flow-builder/flows/{id}
     */
    public function deleteFlow($id)
    {
        $flow = Flow::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('delete', $flow);

        $flow->tasks()->delete();
        $flow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Flujo eliminado exitosamente',
        ], 200);
    }

    /**
     * Crear tarea dentro del flujo
     * POST /api/v1/flow-builder/tasks
     */
    public function createTask(Request $request)
    {
        // Autorización mediante Policy
        Gate::authorize('create', Task::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'flow_id' => 'required|exists:flows,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'status' => 'nullable|in:pending,blocked,in_progress,paused,completed,cancelled',
            'is_milestone' => 'nullable|boolean',
            'allow_attachments' => 'nullable|boolean',
            'estimated_start_at' => 'nullable|date',
            'estimated_end_at' => 'nullable|date',
            'depends_on_task_id' => 'nullable|exists:tasks,id',
            'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        ]);

        $task = Task::create([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'data' => $task->load(['flow', 'assignee', 'parentTask']),
        ], 201);
    }

    /**
     * Actualizar estructura de tarea
     * PUT /api/v1/flow-builder/tasks/{id}
     */
    public function updateTaskStructure(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('updateStructure', $task);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'assignee_id' => 'nullable|exists:users,id',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'is_milestone' => 'sometimes|boolean',
            'allow_attachments' => 'sometimes|boolean',
            'estimated_start_at' => 'nullable|date',
            'estimated_end_at' => 'nullable|date',
            'depends_on_task_id' => 'nullable|exists:tasks,id',
            'depends_on_milestone_id' => 'nullable|exists:tasks,id',
            'order' => 'sometimes|integer|min:0',
        ]);

        $task->update([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estructura de tarea actualizada exitosamente',
            'data' => $task->load(['flow', 'assignee', 'parentTask', 'subtasks']),
        ], 200);
    }

    /**
     * Eliminar tarea
     * DELETE /api/v1/flow-builder/tasks/{id}
     */
    public function deleteTask($id)
    {
        $task = Task::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada exitosamente',
        ], 200);
    }

    /**
     * Configurar dependencias de tarea
     * PUT /api/v1/flow-builder/tasks/{id}/dependencies
     */
    public function configureDependencies(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('manageDependencies', $task);

        $validated = $request->validate([
            'depends_on_task_id' => 'nullable|exists:tasks,id',
            'depends_on_milestone_id' => 'nullable|exists:tasks,id',
        ]);

        // Validar que no se creen dependencias circulares
        if (isset($validated['depends_on_task_id'])) {
            if ($validated['depends_on_task_id'] === $task->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una tarea no puede depender de sí misma',
                ], 422);
            }
        }

        $task->update([
            ...$validated,
            'last_updated_by' => $request->user()->id,
        ]);

        // Recalcular estado de bloqueo
        $task->is_blocked = $task->checkIsBlocked();
        $task->save();

        return response()->json([
            'success' => true,
            'message' => 'Dependencias configuradas exitosamente',
            'data' => $task->load(['dependsOnTask', 'dependsOnMilestone']),
        ], 200);
    }
}
