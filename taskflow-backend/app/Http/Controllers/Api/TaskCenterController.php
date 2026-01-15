<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Task Center Controller
 *
 * Controlador exclusivo para el módulo Task Center.
 * Accesible por usuarios asignados a tareas.
 *
 * Responsabilidades:
 * - Ver tareas asignadas
 * - Actualizar progreso
 * - Cambiar estado (pending → in_progress → completed)
 * - Registrar tiempos
 * - Subir archivos adjuntos
 *
 * NO PERMITE:
 * - Modificar estructura
 * - Cambiar dependencias
 * - Reasignar tareas
 */
class TaskCenterController extends Controller
{


    /**
     * Obtener tareas asignadas al usuario actual
     * GET /api/v1/task-center/my-tasks
     */
    public function myTasks(Request $request)
    {
        $user = $request->user();

        $query = Task::with([
            'flow',
            'parentTask',
            'subtasks',
            'dependsOnTask',
            'dependsOnMilestone',
            'attachments'
        ])
        ->where('assignee_id', $user->id);

        // Filtros opcionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('flow_id')) {
            $query->where('flow_id', $request->flow_id);
        }

        $tasks = $query->orderBy('priority', 'desc')
            ->orderBy('estimated_end_at', 'asc')
            ->get();

        // Calcular alertas de SLA
        $tasksWithSLA = $tasks->map(function ($task) {
            $task->sla_status = $this->calculateSLAStatus($task);
            return $task;
        });

        return response()->json([
            'success' => true,
            'data' => $tasksWithSLA,
        ], 200);
    }

    /**
     * Ver detalle de una tarea
     * GET /api/v1/task-center/tasks/{id}
     */
    public function show($id)
    {
        $task = Task::with([
            'flow',
            'assignee',
            'parentTask',
            'subtasks',
            'dependsOnTask',
            'dependsOnMilestone',
            'attachments.uploader'
        ])->findOrFail($id);

        // Autorización: solo puede ver si es el asignado o es PM/Admin
        Gate::authorize('view', $task);

        $task->sla_status = $this->calculateSLAStatus($task);

        return response()->json([
            'success' => true,
            'data' => $task,
        ], 200);
    }

    /**
     * Actualizar estado y progreso de tarea (EJECUCIÓN)
     * PUT /api/v1/task-center/tasks/{id}/execute
     */
    public function executeTask(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // Autorización mediante Policy
        Gate::authorize('execute', $task);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,in_progress,paused,completed',
            'progress' => 'sometimes|integer|min:0|max:100',
        ]);

        // 🎯 MOTOR DE CONTROL DE FLUJOS
        if (isset($validated['status'])) {
            $task->refresh();
            $newStatus = $validated['status'];

            // 1. Verificar Bloqueo
            if ($task->is_blocked && in_array($newStatus, ['in_progress', 'completed'])) {
                $blockingReasons = [];

                if ($task->depends_on_task_id) {
                    $precedentTask = Task::find($task->depends_on_task_id);
                    if ($precedentTask && $precedentTask->status !== 'completed') {
                        $blockingReasons[] = "la tarea '{$precedentTask->title}' (ID: {$precedentTask->id})";
                    }
                }

                if ($task->depends_on_milestone_id) {
                    $milestone = Task::find($task->depends_on_milestone_id);
                    if ($milestone && $milestone->status !== 'completed') {
                        $blockingReasons[] = "el milestone '{$milestone->title}' (ID: {$milestone->id})";
                    }
                }

                $blockMessage = !empty($blockingReasons)
                    ? "Esta tarea está bloqueada. Debe completarse " . implode(' y ', $blockingReasons) . " primero."
                    : "Esta tarea está bloqueada por dependencias no cumplidas.";

                return response()->json([
                    'success' => false,
                    'message' => "🔒 Acción prohibida: {$blockMessage}",
                ], 403);
            }

            // 2. Validar adjuntos obligatorios al completar
            if ($newStatus === 'completed' && $task->allow_attachments) {
                if ($task->attachments()->count() === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => "⚠️ Requisito faltante: Debes adjuntar al menos un documento para completar esta tarea.",
                    ], 422);
                }
            }

            // 3. Registrar tiempos automáticamente
            if ($newStatus === 'in_progress' && !$task->actual_start_at) {
                $validated['actual_start_at'] = now();
            }

            if ($newStatus === 'completed' && !$task->actual_end_at) {
                $validated['actual_end_at'] = now();
                $validated['progress'] = 100; // Auto-completar progreso
            }
        }

        $task->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tarea actualizada exitosamente',
            'data' => $task->load(['flow', 'assignee', 'attachments']),
        ], 200);
    }

    /**
     * Calcular estado de SLA
     */
    private function calculateSLAStatus(Task $task): ?array
    {
        if (!$task->estimated_end_at || $task->status === 'completed') {
            return null;
        }

        $now = now();
        $deadline = $task->estimated_end_at;
        $diffDays = $now->diffInDays($deadline, false);

        if ($diffDays < 0) {
            return [
                'level' => 'critical',
                'message' => "⚠️ Vencida hace " . abs($diffDays) . " día(s)",
                'days_overdue' => abs($diffDays),
            ];
        } elseif ($diffDays == 0) {
            return [
                'level' => 'warning',
                'message' => '⏰ Vence HOY',
                'days_remaining' => 0,
            ];
        } elseif ($diffDays == 1) {
            return [
                'level' => 'warning',
                'message' => '⏰ Vence MAÑANA',
                'days_remaining' => 1,
            ];
        } elseif ($diffDays <= 2) {
            return [
                'level' => 'info',
                'message' => "⏰ Vence en {$diffDays} días",
                'days_remaining' => $diffDays,
            ];
        }

        return null;
    }
}
