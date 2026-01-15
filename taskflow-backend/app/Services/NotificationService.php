<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\Flow;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Crear notificación cuando una tarea se bloquea
     */
    public static function taskBlocked(Task $task): void
    {
        if (!$task->assignee_id) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea bloqueada', [
            'task_id' => $task->id,
            'assignee_id' => $task->assignee_id
        ]);

        $notification = Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_blocked',
            'title' => 'Tarea Bloqueada',
            'message' => "La tarea '{$task->title}' ha sido bloqueada por dependencias",
            'priority' => 'medium',
        ]);

        // Cargar relaciones para broadcast
        $notification->load(['task', 'flow']);

        // Broadcast event para notificación en tiempo real
        if ($notification) {
            event(new \App\Events\NotificationSent($notification));
        }
    }

    /**
     * Crear notificación cuando una tarea se desbloquea
     */
    public static function taskUnblocked(Task $task): void
    {
        if (!$task->assignee_id) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea desbloqueada', [
            'task_id' => $task->id,
            'assignee_id' => $task->assignee_id
        ]);

        $notification = Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_unblocked',
            'title' => '¡Tarea Desbloqueada!',
            'message' => "La tarea '{$task->title}' ha sido desbloqueada y puede iniciarse",
            'priority' => 'medium',
        ]);

        // Cargar relaciones para broadcast
        $notification->load(['task', 'flow']);

        // Broadcast event para notificación en tiempo real
        if ($notification) {
            event(new \App\Events\NotificationSent($notification));
        }
    }

    /**
     * Crear notificación cuando se asigna una tarea a un usuario
     */
    public static function taskAssigned(Task $task, int $newAssigneeId): void
    {
        Log::info('📬 Creando notificación: Tarea asignada', [
            'task_id' => $task->id,
            'assignee_id' => $newAssigneeId
        ]);

        $notification = Notification::create([
            'user_id' => $newAssigneeId,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_assigned',
            'title' => 'Nueva Tarea Asignada',
            'message' => "Se te ha asignado la tarea '{$task->title}'",
            'priority' => 'medium',
        ]);

        // Cargar relaciones para broadcast
        $notification->load(['task', 'flow']);

        // Broadcast event para notificación en tiempo real
        if ($notification) {
            event(new \App\Events\NotificationSent($notification));
        }
    }

    /**
     * Notificar cuando cambia el asignado de una tarea
     */
    public static function taskAssigneeChanged(Task $task, ?User $oldAssignee, User $newAssignee): void
    {
        Log::info('📬 Notificando cambio de asignado de tarea', [
            'task_id' => $task->id,
            'old_assignee_id' => $oldAssignee?->id,
            'new_assignee_id' => $newAssignee->id
        ]);

        // Notificar al nuevo asignado
        $newNotification = Notification::create([
            'user_id' => $newAssignee->id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_assigned',
            'title' => 'Tarea Reasignada',
            'message' => "Ahora eres responsable de la tarea '{$task->title}'",
            'priority' => 'medium',
            'data' => [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'previous_assignee' => $oldAssignee?->name ?? 'Ninguno',
                'assigned_at' => now()->toISOString(),
            ],
        ]);

        // Cargar relaciones para broadcast
        $newNotification->load(['task', 'flow']);

        // Notificar al asignado anterior (opcional)
        if ($oldAssignee) {
            $oldNotification = Notification::create([
                'user_id' => $oldAssignee->id,
                'task_id' => $task->id,
                'flow_id' => $task->flow_id,
                'type' => 'task_reassigned',
                'title' => 'Tarea Reasignada',
                'message' => "La tarea '{$task->title}' ha sido reasignada a {$newAssignee->name}",
                'priority' => 'low',
                'data' => [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'new_assignee' => $newAssignee->name,
                    'reassigned_at' => now()->toISOString(),
                ],
            ]);

            // Cargar relaciones para broadcast
            $oldNotification->load(['task', 'flow']);

            if ($oldNotification) {
                event(new \App\Events\NotificationSent($oldNotification));
            }
        }

        // Broadcast event para el nuevo asignado
        if ($newNotification) {
            event(new \App\Events\NotificationSent($newNotification));
        }
    }

    /**
     * Crear notificación cuando una tarea se completa
     */
    public static function taskCompleted(Task $task): void
    {
        // Notificar al creador del flujo
        $flow = $task->flow;
        if (!$flow || !$flow->created_by) {
            return;
        }

        // No notificar si el creador es quien completó la tarea
        if ($flow->created_by === $task->assignee_id) {
            return;
        }

        // Verificar si el creador es un Admin o PM (para evitar ruido a usuarios normales)
        $creator = User::find($flow->created_by);
        if (!$creator || !in_array($creator->role, ['admin', 'project_manager', 'pm'])) {
            return;
        }

        Log::info('📬 Creando notificación: Tarea completada', [
            'task_id' => $task->id,
            'flow_creator' => $flow->created_by
        ]);

        $notification = Notification::create([
            'user_id' => $flow->created_by,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_completed',
            'title' => 'Tarea Completada',
            'message' => "La tarea '{$task->title}' ha sido completada por " . ($task->assignee ? $task->assignee->name : 'un usuario'),
            'priority' => 'medium',
        ]);

        // Cargar relaciones para broadcast
        $notification->load(['task', 'flow']);

        // Broadcast event para notificación en tiempo real
        if ($notification) {
            event(new \App\Events\NotificationSent($notification));
        }
    }

    /**
     * Crear notificación cuando se completa un milestone
     */
    public static function milestoneCompleted(Task $milestone): void
    {
        $flow = $milestone->flow;
        if (!$flow) {
            return;
        }

        Log::info('📬 Creando notificación: Milestone completado', [
            'milestone_id' => $milestone->id,
            'flow_id' => $flow->id
        ]);

        // Notificar al creador del flujo (Solo si es Admin/PM)
        if ($flow->created_by) {
             $creator = User::find($flow->created_by);
             if ($creator && in_array($creator->role, ['admin', 'project_manager', 'pm'])) {
                $notification = Notification::create([
                    'user_id' => $flow->created_by,
                    'task_id' => $milestone->id,
                    'flow_id' => $milestone->flow_id,
                    'type' => 'milestone_completed',
                    'title' => '🎯 Milestone Completado',
                    'message' => "El milestone '{$milestone->title}' ha sido completado",
                    'priority' => 'high',
                ]);

                // Cargar relaciones para broadcast
                $notification->load(['task', 'flow']);

                // Broadcast event para notificación en tiempo real
                if ($notification) {
                    event(new \App\Events\NotificationSent($notification));
                }
             }
        }

        // Notificar a todos los asignados de tareas que dependían de este milestone
        $dependentTasks = Task::where('depends_on_milestone_id', $milestone->id)
            ->whereNotNull('assignee_id')
            ->get();

        foreach ($dependentTasks as $task) {
            // Evitar notificar al mismo creador dos veces si también es asignado
            if ($task->assignee_id !== $flow->created_by) {
                $notification = Notification::create([
                    'user_id' => $task->assignee_id,
                    'task_id' => $task->id,
                    'flow_id' => $task->flow_id,
                    'type' => 'milestone_completed',
                    'title' => '🎯 Milestone Completado',
                    'message' => "El milestone '{$milestone->title}' ha sido completado. Tu tarea '{$task->title}' puede continuar",
                    'priority' => 'medium',
                ]);

                // Cargar relaciones para broadcast
                $notification->load(['task', 'flow']);

                // Broadcast event para notificación en tiempo real
                if ($notification) {
                    event(new \App\Events\NotificationSent($notification));
                }
            }
        }
    }

    /**
     * Notificar al usuario asignado como responsable de un flujo
     */
    public static function flowAssigned(Flow $flow): void
    {
        if (!$flow->responsible_id) {
            return;
        }

        Log::info('📬 Creando notificación: Flujo asignado', [
            'flow_id' => $flow->id,
            'responsible_id' => $flow->responsible_id
        ]);

        try {
            $createdByName = 'Sistema';
            if ($flow->created_by) {
                $creator = User::find($flow->created_by);
                if ($creator) {
                    $createdByName = $creator->name;
                }
            }

            $notification = Notification::create([
                'user_id' => $flow->responsible_id,
                'flow_id' => $flow->id,
                'type' => 'flow_assigned',
                'title' => 'Flujo Asignado',
                'message' => "Se te ha asignado como responsable del flujo: {$flow->name}",
                'priority' => 'medium',
                'data' => [
                    'flow_id' => $flow->id,
                    'flow_name' => $flow->name,
                    'assigned_by' => $createdByName,
                    'assigned_at' => now()->toISOString(),
                ],
            ]);

            // Cargar relaciones para broadcast
            $notification->load(['task', 'flow']);

            // Broadcast event para notificación en tiempo real
            if ($notification) {
                event(new \App\Events\NotificationSent($notification));
            }
        } catch (\Throwable $e) {
            Log::error('Error en flowAssigned: ' . $e->getMessage());
        }
    }

    /**
     * Notificar cuando cambia el responsable de un flujo
     */
    public static function flowResponsibleChanged(Flow $flow, ?User $oldResponsible, User $newResponsible): void
    {
        Log::info('📬 Notificando cambio de responsable de flujo', [
            'flow_id' => $flow->id,
            'old_responsible_id' => $oldResponsible?->id,
            'new_responsible_id' => $newResponsible->id
        ]);

        // Notificar al nuevo responsable
        $newNotification = Notification::create([
            'user_id' => $newResponsible->id,
            'flow_id' => $flow->id,
            'type' => 'flow_assigned',
            'title' => 'Flujo Reasignado',
            'message' => "Ahora eres responsable del flujo: {$flow->name}",
            'priority' => 'medium',
            'data' => [
                'flow_id' => $flow->id,
                'flow_name' => $flow->name,
                'previous_responsible' => $oldResponsible?->name ?? 'Ninguno',
                'assigned_at' => now()->toISOString(),
            ],
        ]);

        // Cargar relaciones para broadcast
        $newNotification->load(['task', 'flow']);

        // Notificar al responsable anterior (opcional)
        if ($oldResponsible) {
            $oldNotification = Notification::create([
                'user_id' => $oldResponsible->id,
                'flow_id' => $flow->id,
                'type' => 'flow_responsible_changed',
                'title' => 'Responsabilidad de Flujo Transferida',
                'message' => "Ya no eres responsable del flujo: {$flow->name}. Nuevo responsable: {$newResponsible->name}",
                'priority' => 'low',
                'data' => [
                    'flow_id' => $flow->id,
                    'flow_name' => $flow->name,
                    'new_responsible' => $newResponsible->name,
                    'changed_at' => now()->toISOString(),
                ],
            ]);

            // Cargar relaciones para broadcast
            $oldNotification->load(['task', 'flow']);

            if ($oldNotification) {
                event(new \App\Events\NotificationSent($oldNotification));
            }
        }

        // Broadcast event para el nuevo responsable
        if ($newNotification) {
            event(new \App\Events\NotificationSent($newNotification));
        }
    }

    /**
     * Notificar cuando se completa un flujo al responsable
     */
    public static function flowCompleted(Flow $flow): void
    {
        // Notificar al responsable del flujo
        if (!$flow->responsible_id) {
            return;
        }

        Log::info('📬 Creando notificación: Flujo completado', [
            'flow_id' => $flow->id,
            'responsible_id' => $flow->responsible_id
        ]);

        $notification = Notification::create([
            'user_id' => $flow->responsible_id,
            'flow_id' => $flow->id,
            'type' => 'flow_completed',
            'title' => '✅ Flujo Completado',
            'message' => "El flujo '{$flow->name}' ha sido completado exitosamente",
            'priority' => 'high',
            'data' => [
                'flow_id' => $flow->id,
                'flow_name' => $flow->name,
                'completed_at' => now()->toISOString(),
            ],
        ]);

        // Cargar relaciones para broadcast
        $notification->load(['task', 'flow']);

        // Broadcast event para notificación en tiempo real
        if ($notification) {
            event(new \App\Events\NotificationSent($notification));
        }
    }
}
