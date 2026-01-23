<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SLAService
{
    /**
     * Limpiar alertas SLA antiguas si tarea ya no está atrasada
     *
     * @param Task $task
     * @return void
     */
    public function clearStaleAlerts(Task $task): void
    {
        $currentStatus = $task->recalculateSLAStatus();

        Log::info('🔍 Verificando alertas SLA obsoletas', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'current_status' => $currentStatus,
            'sla_due_date' => $task->sla_due_date,
            'assignee_id' => $task->assignee_id,
        ]);

        // Si no hay retraso, eliminar notificaciones antiguas de SLA
        if ($currentStatus === 'none') {
            $deletedCount = Notification::where('task_id', $task->id)
                ->whereIn('type', ['sla_warning', 'sla_warning_48h', 'task_overdue'])
                ->delete();

            if ($deletedCount > 0) {
                Log::info("✅ Eliminadas {$deletedCount} notificaciones SLA obsoletas para tarea #{$task->id}");

                // Notificar al usuario que la alerta se resolvió
                if ($task->assignee_id) {
                    Notification::create([
                        'user_id' => $task->assignee_id,
                        'task_id' => $task->id,
                        'flow_id' => $task->flow_id,
                        'type' => 'sla_resolved',
                        'title' => 'Alerta de SLA resuelta',
                        'message' => "La fecha de '{$task->title}' fue actualizada. Alerta cancelada.",
                        'priority' => 'low',
                        'is_read' => false,
                    ]);

                    Log::info('📬 Notificación de resolución de SLA creada', [
                        'task_id' => $task->id,
                        'user_id' => $task->assignee_id,
                    ]);
                }
            }
        } else {
            Log::info("⚠️ Tarea #{$task->id} sigue en estado SLA: {$currentStatus}");
        }
    }

    /**
     * Actualizar estado de SLA de la tarea
     *
     * @param Task $task
     * @param bool $fireEvent Si se debe disparar el evento de cambio de estado
     * @return string El nuevo estado SLA ('none', 'warning', 'critical')
     */
    public function updateSLAStatus(Task $task, bool $fireEvent = true): string
    {
        $oldStatus = $task->sla_breached ? 'warning' : 'none';
        $newStatus = $task->recalculateSLAStatus();

        // Si el estado cambió, actualizar campos de la tarea
        if ($oldStatus !== $newStatus) {
            if ($newStatus === 'none') {
                $task->sla_breached = false;
                $task->sla_breach_at = null;
                $task->sla_days_overdue = 0;
            } else {
                $task->sla_breached = true;
                if (!$task->sla_breach_at) {
                    $task->sla_breach_at = now();
                }
                $task->sla_days_overdue = now()->diffInDays($task->sla_due_date);
            }

            $task->saveQuietly(); // Guardar sin disparar eventos para evitar loop

            Log::info('🔄 Estado SLA actualizado', [
                'task_id' => $task->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }

        return $newStatus;
    }
}
