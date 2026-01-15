<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Notification;
use App\Models\User;
use App\Events\NotificationSent;
use App\Events\SlaBreached;
use Illuminate\Support\Facades\Log;

class SlaNotificationService
{
    /**
     * Enviar notificación al responsable de la tarea
     */
    public function notifyAssignee(Task $task): void
    {
        if (!$task->assignee_id) {
            Log::warning("Task {$task->id} no tiene assignee para notificar");
            return;
        }

        // Crear notificación para el responsable
        $notification = Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'sla_warning',
            'title' => 'Tarea con retraso de SLA',
            'message' => "La tarea '{$task->title}' está retrasada por {$task->sla_days_overdue} día(s). Por favor, actualiza su estado.",
            'priority' => 'urgent',
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'days_overdue' => $task->sla_days_overdue,
                'sla_due_date' => $task->sla_due_date?->toIso8601String(),
            ],
            'is_read' => false,
        ]);

        // Disparar evento en tiempo real
        broadcast(new NotificationSent($notification))->toOthers();
        broadcast(new SlaBreached($task, false))->toOthers();

        // Marcar como notificado
        $task->update([
            'sla_notified_assignee' => true,
            'sla_notified_at' => now(),
        ]);

        Log::info("Notificación de SLA enviada al responsable de la tarea {$task->id}");
    }

    /**
     * Escalar al supervisor/PM
     */
    public function escalateToSupervisor(Task $task): void
    {
        $supervisor = $task->getSupervisor();

        if (!$supervisor) {
            Log::warning("No se encontró supervisor para escalar la tarea {$task->id}");
            return;
        }

        // Crear notificación para el supervisor
        $supervisorNotification = Notification::create([
            'user_id' => $supervisor->id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'sla_escalation',
            'title' => 'Escalamiento de tarea con retraso crítico',
            'message' => "La tarea '{$task->title}' está retrasada por {$task->sla_days_overdue} días y requiere atención inmediata.",
            'priority' => 'urgent',
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'assignee_id' => $task->assignee_id,
                'assignee_name' => $task->assignee?->name,
                'days_overdue' => $task->sla_days_overdue,
                'sla_due_date' => $task->sla_due_date?->toIso8601String(),
            ],
            'is_read' => false,
        ]);

        // Disparar evento de notificación al supervisor
        broadcast(new NotificationSent($supervisorNotification))->toOthers();

        // También notificar al responsable sobre el escalamiento
        if ($task->assignee_id && $task->assignee_id !== $supervisor->id) {
            $assigneeNotification = Notification::create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'flow_id' => $task->flow_id,
                'type' => 'sla_escalation_notice',
                'title' => 'Tarea escalada al supervisor',
                'message' => "La tarea '{$task->title}' ha sido escalada al supervisor debido al retraso de {$task->sla_days_overdue} días.",
                'priority' => 'urgent',
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'supervisor_name' => $supervisor->name,
                    'days_overdue' => $task->sla_days_overdue,
                ],
                'is_read' => false,
            ]);

            // Disparar evento de notificación al assignee
            broadcast(new NotificationSent($assigneeNotification))->toOthers();
        }

        // Disparar evento de escalamiento
        broadcast(new SlaBreached($task, true))->toOthers();

        // Marcar como escalado
        $task->update([
            'sla_escalated' => true,
            'sla_escalated_at' => now(),
        ]);

        Log::info("Tarea {$task->id} escalada al supervisor {$supervisor->id}");
    }

    /**
     * Procesar todas las tareas con SLA vencido
     */
    public function processOverdueTasks(): array
    {
        $stats = [
            'checked' => 0,
            'notified' => 0,
            'escalated' => 0,
        ];

        // Actualizar estados de SLA de todas las tareas activas
        $activeTasks = Task::whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('sla_due_date')
            ->get();

        foreach ($activeTasks as $task) {
            $task->checkSlaStatus();
            $stats['checked']++;
        }

        // Notificar a responsables (+1 día)
        $tasksToNotify = Task::needsAssigneeNotification()->get();

        foreach ($tasksToNotify as $task) {
            $this->notifyAssignee($task);
            $stats['notified']++;
        }

        // Escalar al supervisor (+2 días)
        $tasksToEscalate = Task::needsEscalation()->get();

        foreach ($tasksToEscalate as $task) {
            $this->escalateToSupervisor($task);
            $stats['escalated']++;
        }

        Log::info('Proceso de SLA completado', $stats);

        return $stats;
    }
}
