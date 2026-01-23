<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Almacena el assignee_id anterior para detectar cambios
     */
    protected static array $previousAssignees = [];

    /**
     * Handle the Task "saving" event.
     * IMPORTANTE: Usamos 'saving' en lugar de 'creating' para asegurar que
     * el valor se establezca DESPUÉS de que todos los atributos estén asignados
     * pero ANTES del INSERT en la base de datos.
     */
    public function saving(Task $task): void
    {
        // Registrar quién modifica (Creación o Actualización)
        if (auth()->check()) {
            $task->last_updated_by = auth()->id();
        }

        // ✅ Auto-sincronizar sla_due_date con estimated_end_at
        // CASO 1: Creación - Si no hay sla_due_date pero sí estimated_end_at
        if (!$task->sla_due_date && $task->estimated_end_at) {
            $task->sla_due_date = $task->estimated_end_at;
            Log::info('📅 Auto-asignando sla_due_date desde estimated_end_at (creación)', [
                'task_id' => $task->id ?? 'new',
                'title' => $task->title ?? 'Sin título',
                'estimated_end_at' => $task->estimated_end_at,
                'sla_due_date' => $task->sla_due_date,
            ]);
        }

        // CASO 2: Actualización - Si cambió estimated_end_at, sincronizar sla_due_date
        if ($task->exists && $task->estimated_end_at) {
            // Verificar si cambió usando getOriginal
            $oldEstimatedEnd = $task->getOriginal('estimated_end_at');
            $newEstimatedEnd = $task->estimated_end_at;

            // Convertir a string para comparación segura
            $oldDate = $oldEstimatedEnd ? \Carbon\Carbon::parse($oldEstimatedEnd)->toDateTimeString() : null;
            $newDate = $newEstimatedEnd ? \Carbon\Carbon::parse($newEstimatedEnd)->toDateTimeString() : null;

            if ($oldDate !== $newDate) {
                $task->sla_due_date = $task->estimated_end_at;
                Log::info('🔄 Sincronizando sla_due_date con estimated_end_at (actualización)', [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'old_estimated_end_at' => $oldDate,
                    'new_estimated_end_at' => $newDate,
                    'new_sla_due_date' => $task->sla_due_date,
                ]);
            }
        }

        // Capture old assignee for updates before returning
        if ($task->exists && $task->isDirty('assignee_id')) {
            self::$previousAssignees[$task->id] = $task->getOriginal('assignee_id');
        }

        // Solo ejecutar en creación, no en actualización (el resto del método)
        if ($task->exists) {
            return;
        }

        // Lógica de Bloqueo Inteligente en Creación
        try {
            // Usar el método unificado del modelo
            $task->is_blocked = $task->checkIsBlocked();

            // IMPORTANTE: Si la tarea está bloqueada, establecer blocked_reason (NO cambiar status)
            // El status sigue siendo 'pending' pero is_blocked = true indica que no se puede ejecutar
            if ($task->is_blocked && !in_array($task->status, ['completed', 'cancelled'])) {
                $task->blocked_reason = 'Esperando tareas precedentes';
                Log::info("🔒 Tarea creada bloqueada por dependencias (is_blocked=true)", [
                    'title' => $task->title,
                    'status' => $task->status,
                    'depends_on_task_id' => $task->depends_on_task_id,
                    'depends_on_milestone_id' => $task->depends_on_milestone_id,
                ]);
            }

            // Log detallado para debugging
            if ($task->depends_on_task_id) {
                $precedent = Task::find($task->depends_on_task_id);
                Log::info("🔍 Verificando dependencia de tarea", [
                    'nueva_tarea' => $task->title,
                    'depende_de_id' => $task->depends_on_task_id,
                    'tarea_precedente_status' => $precedent ? $precedent->status : 'NO ENCONTRADA',
                    'resultado_is_blocked' => $task->is_blocked,
                    'status_final' => $task->status
                ]);
            }

            Log::info($task->is_blocked ? "🔒 Tarea creada BLOQUEADA" : "🔓 Tarea creada LIBRE", [
                'title' => $task->title,
                'depends_on_task_id' => $task->depends_on_task_id,
                'depends_on_milestone_id' => $task->depends_on_milestone_id,
                'is_blocked' => $task->is_blocked,
                'status' => $task->status
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error calculando bloqueo en creación: ' . $e->getMessage());
        }
    }

    /**
     * Handle the Task "created" event.
     * Notificar asignación inicial y enviar notificación en tiempo real.
     */
    public function created(Task $task): void
    {
        if ($task->assignee_id) {
            Log::info('📬 Notificando creación de tarea asignada', [
                'task_id' => $task->id,
                'title' => $task->title,
                'assignee_id' => $task->assignee_id
            ]);

            // Crear la notificación
            $notification = \App\Models\Notification::create([
                'user_id' => $task->assignee_id,
                'task_id' => $task->id,
                'flow_id' => $task->flow_id,
                'type' => 'task_assigned',
                'title' => 'Nueva Tarea Asignada',
                'message' => "Se te ha asignado la tarea '{$task->title}'",
                'priority' => 'medium',
            ]);

            // Cargar la tarea con relaciones para el broadcast
            $notification->load(['task', 'flow']);

            // Broadcast event para notificación en tiempo real
            if ($notification) {
    broadcast(new \App\Events\NotificationSent($notification))->toOthers();
}
        }
    }

    /**
     * Handle the Task "updating" event.
     * Recalcula is_blocked cuando cambian las dependencias.
     * Calcula progress automáticamente basado en el estado.
     * Genera notificaciones automáticas.
     */
    public function updating(Task $task): void
    {
        // 0. Guardar el assignee_id anterior si cambió
        if ($task->isDirty('assignee_id')) {
            self::$previousAssignees[$task->id] = $task->getOriginal('assignee_id');
        }

        // 1. Recalcular is_blocked si cambiaron las dependencias
        if ($task->isDirty('depends_on_task_id') || $task->isDirty('depends_on_milestone_id')) {
            Log::info('🔄 Dependencias cambiaron, recalculando is_blocked', [
                'task_id' => $task->id,
                'depends_on_task_id' => $task->depends_on_task_id,
                'depends_on_milestone_id' => $task->depends_on_milestone_id,
            ]);

            // Verificar si todas las dependencias están completadas
            $shouldBeBlocked = false;

            if ($task->depends_on_task_id) {
                $precedentTask = Task::find($task->depends_on_task_id);
                if ($precedentTask && $precedentTask->status !== 'completed') {
                    $shouldBeBlocked = true;
                    Log::info("⏸️ Tarea precedente {$precedentTask->id} no completada");
                }
            }

            if ($task->depends_on_milestone_id) {
                $milestone = Task::find($task->depends_on_milestone_id);
                if ($milestone && $milestone->status !== 'completed') {
                    $shouldBeBlocked = true;
                    Log::info("⏸️ Milestone {$milestone->id} no completado");
                }
            }

            $task->is_blocked = $shouldBeBlocked;
            Log::info($shouldBeBlocked ? '🔒 Tarea bloqueada' : '🔓 Tarea desbloqueada', [
                'is_blocked' => $task->is_blocked
            ]);
        }

        // 2. Detectar cambio en is_blocked para notificaciones
        if ($task->isDirty('is_blocked')) {
            $wasBlocked = $task->getOriginal('is_blocked');
            $isNowBlocked = $task->is_blocked;

            if (!$wasBlocked && $isNowBlocked) {
                // Se bloqueó
                NotificationService::taskBlocked($task);
            } elseif ($wasBlocked && !$isNowBlocked) {
                // Se desbloqueó
                NotificationService::taskUnblocked($task);
            }
        }

        // 3. Calcular progreso basado en cambio de estado
        // IMPORTANTE: Solo para tareas normales, NO para milestones
        // Los milestones calculan su progreso desde las subtareas
        if ($task->isDirty('status') && !$task->is_milestone) {
            $this->calculateProgressFromStatus($task);
        }
    }

    /**
     * Calcular progreso basado en el estado
     * IMPORTANTE: Solo para tareas normales, NO para milestones
     */
    protected function calculateProgressFromStatus(Task $task): void
    {
        // Los milestones calculan su progreso desde sus subtareas, no desde su estado
        if ($task->is_milestone) {
            Log::info("⏭️ Saltando cálculo de progreso para milestone (se calcula desde subtareas)", [
                'task_id' => $task->id ?? 'new',
                'title' => $task->title
            ]);
            return;
        }

        $oldProgress = $task->progress ?? 0;

        switch ($task->status) {
            case 'pending':
                $task->progress = 0;
                break;
            case 'in_progress':
                // Si está en progreso y tenía 0%, ponerlo en 50%
                // Si ya tenía progreso, mantenerlo (permite ajustes manuales)
                if ($task->progress === 0 || is_null($task->progress)) {
                    $task->progress = 50;
                }
                break;
            case 'completed':
                $task->progress = 100;
                break;
            case 'cancelled':
                $task->progress = 0;
                break;
            case 'paused':
                // Mantener el progreso actual
                break;
        }

        if ($oldProgress !== $task->progress) {
            Log::info("📊 Progress auto-calculado: {$oldProgress}% → {$task->progress}%", [
                'task_id' => $task->id ?? 'new',
                'status' => $task->status
            ]);
        }
    }
    /**
     * Handle the Task "updated" event.
     * Dispara la liberación en cascada al completar una tarea.
     * Genera notificaciones de tarea/milestone completado.
     */
    public function updated(Task $task): void
    {
        // 0. Detectar cambio de asignado DESPUÉS del guardado
        if ($task->wasChanged('assignee_id')) {
            $newAssigneeId = $task->assignee_id;
            $oldAssigneeId = self::$previousAssignees[$task->id] ?? null;

            Log::info(' [UPDATED] Cambio de asignado detectado', [
                'task_id' => $task->id,
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $newAssigneeId
            ]);

            if ($newAssigneeId) {
                // Si había un asignado anterior, usar notificación de cambio
                if ($oldAssigneeId && $oldAssigneeId !== $newAssigneeId) {
                    $oldAssignee = \App\Models\User::find($oldAssigneeId);
                    $newAssignee = \App\Models\User::find($newAssigneeId);

                    if ($newAssignee) {
                        Log::info('📬 [UPDATED] Llamando a taskAssigneeChanged');
                        NotificationService::taskAssigneeChanged($task, $oldAssignee, $newAssignee);
                    }
                } else {
                    // Primera asignación
                    Log::info('📬 [UPDATED] Llamando a taskAssigned (primera vez)');
                    NotificationService::taskAssigned($task, $newAssigneeId);
                }
            }

            // Limpiar el almacenamiento temporal
            unset(self::$previousAssignees[$task->id]);
        }

        // NUEVO: Detectar cambios de fechas
        $this->checkDateChanges($task);

        // NUEVO: Recalcular SLA si cambiaron fechas relevantes
        $dateFields = ['estimated_end_at', 'sla_due_date', 'estimated_start_at', 'status'];

        $dateChanged = false;
        foreach ($dateFields as $field) {
            if ($task->wasChanged($field)) {
                $dateChanged = true;
                break;
            }
        }

        if ($dateChanged) {
            Log::info('📅 Fechas o estado cambiaron, recalculando SLA', [
                'task_id' => $task->id,
                'title' => $task->title,
                'sla_due_date' => $task->sla_due_date,
                'estimated_end_at' => $task->estimated_end_at,
                'status' => $task->status,
            ]);

            // Calcular estado ANTERIOR basado en la fecha original
            $oldSlaDate = $task->getOriginal('sla_due_date');
            $oldStatus = 'none';

            if ($oldSlaDate && !in_array($task->getOriginal('status'), ['completed', 'cancelled'])) {
                $now = now();
                $oldDueDate = \Carbon\Carbon::parse($oldSlaDate);

                if ($now->isAfter($oldDueDate)) {
                    $hoursOverdue = $now->diffInHours($oldDueDate, false);
                    if ($hoursOverdue >= 48) {
                        $oldStatus = 'critical';
                    } elseif ($hoursOverdue >= 24) {
                        $oldStatus = 'warning';
                    }
                }
            }

            // Calcular estado NUEVO
            $newStatus = $task->recalculateSLAStatus();

            Log::info('🔄 Comparando estados SLA', [
                'task_id' => $task->id,
                'old_sla_date' => $oldSlaDate,
                'new_sla_date' => $task->sla_due_date,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // SIEMPRE disparar evento si hubo cambio de fecha, incluso si status es igual
            // Esto asegura que el frontend se actualice
            if ($oldStatus !== $newStatus || $task->wasChanged('sla_due_date') || $task->wasChanged('estimated_end_at')) {
                Log::info('🚨 Disparando evento SLAStatusChanged', [
                    'task_id' => $task->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $oldStatus !== $newStatus ? 'status_changed' : 'date_changed',
                ]);

                event(new \App\Events\SLAStatusChanged($task, $oldStatus, $newStatus));
            }

            // Limpiar alertas antiguas si ya no hay retraso
            $slaService = app(\App\Services\SLAService::class);
            $slaService->clearStaleAlerts($task);
        }

        // 1. Solo actuamos si el estado cambió A 'completed'
        if ($task->isDirty('status') && $task->status === 'completed') {
            Log::info(' Tarea completada, liberando dependientes', [
                'task_id' => $task->id,
                'title' => $task->title,
            ]);

            // 🔔 Generar notificación de tarea completada
            NotificationService::taskCompleted($task);

            // 🔔 Si es milestone, generar notificación especial
            if ($task->is_milestone) {
                NotificationService::milestoneCompleted($task);
            }

            // 🔔 Resolver alertas SLA automáticamente
            if (config('sla.auto_resolve', true)) {
                $this->resolveSLAAlerts($task);
            }

            // 2. Buscar tareas que dependían de esta (como tarea precedente)
            $taskDependents = Task::where('depends_on_task_id', $task->id)->get();
            Log::info(" Encontradas {$taskDependents->count()} tareas dependientes (depends_on_task_id)");

            foreach ($taskDependents as $dependent) {
                Log::info(" Procesando tarea dependiente {$dependent->id}: {$dependent->title}");
                $this->checkAndUnlock($dependent);
            }

            // 3. Buscar tareas que dependían de esta (como milestone)
            $milestoneDependents = Task::where('depends_on_milestone_id', $task->id)->get();
            Log::info(" Encontradas {$milestoneDependents->count()} tareas dependientes (depends_on_milestone_id)");

            foreach ($milestoneDependents as $dependent) {
                Log::info(" Procesando tarea dependiente de milestone: {$dependent->id}");
                $this->checkAndUnlock($dependent);
            }
        }
        
        // 4. Lógica de Re-bloqueo: Si se reabre una tarea completada
        if ($task->isDirty('status') && 
            $task->status !== 'completed' && 
            $task->getOriginal('status') === 'completed') {
            
            Log::warning(" Tarea {$task->id} reabierta. Re-bloqueando dependientes.");
            
            // Re-bloquear las tareas que dependían de esta
            Task::where('depends_on_task_id', $task->id)
                ->where('is_blocked', false)
                ->update(['is_blocked' => true]);
            
            // Re-bloquear las tareas que dependían de este milestone
            Task::where('depends_on_milestone_id', $task->id)
                ->where('is_blocked', false)
                ->update(['is_blocked' => true]);
        }
    }

    /**
     * Handle the Task "saved" event (created or updated).
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    /**
     * Handle the Task "saved" event (created or updated).
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    public function saved(Task $task): void
    {
        // 1. Primero actualizamos el padre (Milestone) para que su progreso esté al día
        $this->updateParentProgress($task);
        
        // 2. Luego actualizamos el flujo, que podría depender del valor actualizado del padre
        $this->updateFlowProgress($task);
    }

    /**
     * Handle the Task "deleted" event.
     * Actualizar progreso del flujo padre y del milestone padre.
     */
    public function deleted(Task $task): void
    {
        $this->updateParentProgress($task);
        $this->updateFlowProgress($task);
    }

    /**
     * Actualizar el progreso general del flujo basado en sus tareas
     */
    protected function updateFlowProgress(Task $task): void
    {
        if (!$task->flow_id) {
            return;
        }

        try {
            $flow = $task->flow;
            if (!$flow) {
                return;
            }

            // Calcular promedio de progreso SOLO de las tareas RAÍZ (Milestones y tareas sueltas)
            // Esto evita que las sub-tareas se cuenten doble (una vez solas y otra dentro del milestone)
            // Y da el peso correcto a los Milestones como unidades de progreso.
            $avgProgress = $flow->rootTasks()->avg('progress') ?? 0;
            $avgProgress = round($avgProgress);

            // Actualizar progreso
            $flow->progress = $avgProgress;

            // Actualizar estado del flujo automáticamente
            if ($avgProgress == 100) {
                $flow->status = 'completed';
                if (!$flow->completed_at) {
                    $flow->completed_at = now();
                }
            } else {
                // Si el progreso es < 100 y estaba como completado, volver a activo
                if ($flow->status === 'completed') {
                    $flow->status = 'active';
                    $flow->completed_at = null;
                }

                // Iniciar el flujo si halla progreso pero no fecha de inicio
                if ($avgProgress > 0 && !$flow->started_at) {
                    $flow->started_at = now();
                }
            }

            $flow->saveQuietly();

            Log::info(" Progreso del flujo actualizado (basado en root tasks): {$flow->id} -> {$avgProgress}% ({$flow->status})");

        } catch (\Exception $e) {
            Log::error(" Error actualizando progreso del flujo {$task->flow_id}: " . $e->getMessage());
        }
    }

    /**
     * Actualizar el progreso del Milestone padre si esta es una subtarea
     */
    protected function updateParentProgress(Task $task): void
    {
        if (!$task->parent_task_id) {
            return;
        }

        try {
            $parent = $task->parentTask;
            if (!$parent) {
                return;
            }

            // Refrescar el padre para asegurar datos actualizados
            $parent->refresh();

            // Si no quedan subtareas, el progreso debería volver a 0 (o al estado base)
            // Esto corrige el problema de hitos que quedan al 100% tras borrar su única tarea
            if ($parent->subtasks()->count() === 0) {
                $newProgress = 0;
            } else {
                $newProgress = $parent->calculateProgress();
            }
             
            // Solo actualizar si hay cambios
            if ($parent->progress !== $newProgress) {
                $parent->progress = $newProgress;
                
                // Actualizar estado del padre automáticamente basado en el nuevo progreso
                if ($newProgress === 100) {
                    if ($parent->status !== 'completed') {
                        $parent->status = 'completed';
                    }
                } else {
                    // Si el progreso es < 100 y estaba completado, volver a in_progress
                    if ($parent->status === 'completed') {
                        $parent->status = 'in_progress';
                    } elseif ($newProgress > 0 && $parent->status === 'pending') {
                        $parent->status = 'in_progress';
                    } elseif ($newProgress === 0 && ($parent->status === 'in_progress' || $parent->status === 'completed')) {
                        $parent->status = 'pending';
                    }
                }
                
                $parent->saveQuietly();
                Log::info(" Progreso del Milestone actualizado: {$parent->id} -> {$newProgress}% ({$parent->status})");
            }
            
        } catch (\Exception $e) {
            Log::error(" Error actualizando progreso del padre {$task->parent_task_id}: " . $e->getMessage());
        }
    }

    /**
     * Verifica si TODAS las dependencias de una tarea se han cumplido y la desbloquea.
     */
    protected function checkAndUnlock(Task $task): void
    {
        // Refrescar la tarea desde la base de datos para evitar datos obsoletos
        $task->refresh();
        
        $canUnlock = true;
        
        // Verificar dependencia de Tarea Precedente
        if ($task->depends_on_task_id) {
            $parentTask = Task::find($task->depends_on_task_id);
            if ($parentTask && $parentTask->status !== 'completed') {
                $canUnlock = false;
                Log::info("⏸ Tarea {$task->id} sigue bloqueada por tarea precedente {$parentTask->id}");
            }
        }
        
        // Verificar dependencia de Hito
        if ($task->depends_on_milestone_id) {
            $milestoneTask = Task::find($task->depends_on_milestone_id);
            if ($milestoneTask && $milestoneTask->status !== 'completed') {
                $canUnlock = false;
                Log::info("⏸ Tarea {$task->id} sigue bloqueada por milestone {$milestoneTask->id}");
            }
        }
        
        // Si no hay dependencias pendientes Y la tarea está bloqueada, la liberamos
        if ($canUnlock && $task->is_blocked) {
            // Preparar datos para actualización
            $updateData = ['is_blocked' => false];

            // IMPORTANTE: Si la tarea está en estado 'blocked', cambiarla a 'pending'
            if ($task->status === 'blocked') {
                $updateData['status'] = 'pending';
                $updateData['blocked_reason'] = null;
                Log::info(" Tarea {$task->id} cambiada de 'blocked' a 'pending'");
            }

            // Si es una subtarea (tiene parent_task_id) y está en pending, cambiarla a in_progress
            if ($task->parent_task_id && $task->status === 'pending') {
                $updateData['status'] = 'in_progress';
                Log::info(" Subtarea {$task->id} cambiada a 'in_progress' automáticamente");
            }

            $task->update($updateData);
            Log::info(" Tarea {$task->id} desbloqueada completamente.", $updateData);
        }
    }

    /**
     * Resolver alertas SLA cuando la tarea se completa
     * Regla 4: Resolución Automática
     */
    private function resolveSLAAlerts(Task $task): void
    {
        Log::info(' Resolviendo alertas SLA para tarea completada', [
            'task_id' => $task->id,
            'title' => $task->title,
        ]);

        // Buscar todas las notificaciones SLA pendientes para esta tarea
        $slaNotifications = \App\Models\Notification::where('task_id', $task->id)
            ->whereIn('type', ['sla_warning', 'sla_escalation', 'sla_escalation_notice'])
            ->where('is_read', false)
            ->get();

        $resolvedCount = 0;

        foreach ($slaNotifications as $notification) {
            // Marcar como leída (resolver visualmente)
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

            $resolvedCount++;
        }

        if ($resolvedCount > 0) {
            Log::info(" {$resolvedCount} alertas SLA resueltas automáticamente para tarea {$task->id}");

            // Crear notificación de resolución para el asignado
            if ($task->assignee_id) {
                $resolutionNotification = \App\Models\Notification::create([
                    'user_id' => $task->assignee_id,
                    'task_id' => $task->id,
                    'flow_id' => $task->flow_id,
                    'type' => 'sla_resolved',
                    'title' => ' SLA Resuelto',
                    'message' => "La tarea '{$task->title}' fue completada exitosamente.",
                    'priority' => 'low',
                    'data' => [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                        'resolved_at' => now()->toIso8601String(),
                        'alerts_resolved' => $resolvedCount,
                    ],
                    'is_read' => false,
                ]);

                // Broadcast notificación de resolución
                broadcast(new \App\Events\NotificationSent($resolutionNotification))->toOthers();

                // Si hubo escalación, también notificar al supervisor
                if ($slaNotifications->where('type', 'sla_escalation')->count() > 0) {
                    $supervisor = $task->getSupervisor();

                    if ($supervisor && $supervisor->id !== $task->assignee_id) {
                        $supervisorResolution = \App\Models\Notification::create([
                            'user_id' => $supervisor->id,
                            'task_id' => $task->id,
                            'flow_id' => $task->flow_id,
                            'type' => 'sla_resolved',
                            'title' => ' SLA Escalado Resuelto',
                            'message' => "La tarea escalada '{$task->title}' fue completada por {$task->assignee->name}.",
                            'priority' => 'low',
                            'data' => [
                                'task_id' => $task->id,
                                'task_title' => $task->title,
                                'resolved_by' => $task->assignee?->name,
                                'resolved_at' => now()->toIso8601String(),
                            ],
                            'is_read' => false,
                        ]);

                        broadcast(new \App\Events\NotificationSent($supervisorResolution))->toOthers();
                    }
                }
            }
        }
    }

    /**
     * Detectar cambios en campos de fecha
     * Sistema de Notificaciones Automáticas para Cambios de Fecha
     */
    private function checkDateChanges(Task $task): void
    {
        $dateFields = [
            'estimated_start_at',
            'estimated_end_at',
            'actual_start_at',
            'actual_end_at',
            'sla_due_date',
            'milestone_target_date',
        ];

        foreach ($dateFields as $field) {
            if ($task->wasChanged($field)) {
                $oldValue = $task->getOriginal($field);
                $newValue = $task->{$field};

                Log::info(' Cambio de fecha detectado', [
                    'task_id' => $task->id,
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);

                // Disparar evento para broadcasting
                event(new \App\Events\TaskDateChanged($task, $field, $oldValue, $newValue));

                // Crear notificación
                $this->notifyDateChange($task, $field, $oldValue, $newValue);
            }
        }
    }

    /**
     * Crear notificación de cambio de fecha
     */
    private function notifyDateChange(Task $task, string $field, $oldDate, $newDate): void
    {
        // Solo notificar si hay un asignado
        if (!$task->assignee_id) {
            return;
        }

        // Obtener el nombre del campo legible
        $fieldNames = [
            'estimated_start_at' => 'Fecha estimada de inicio',
            'estimated_end_at' => 'Fecha estimada de finalización',
            'actual_start_at' => 'Fecha real de inicio',
            'actual_end_at' => 'Fecha real de finalización',
            'sla_due_date' => 'Fecha de vencimiento SLA',
            'milestone_target_date' => 'Fecha objetivo del milestone',
        ];

        $fieldLabel = $fieldNames[$field] ?? $field;

        // Formato para mostrar
        $oldDateFormatted = $oldDate ? \Carbon\Carbon::parse($oldDate)->format('d/m/Y H:i') : 'Sin fecha';
        $newDateFormatted = $newDate ? \Carbon\Carbon::parse($newDate)->format('d/m/Y H:i') : 'Sin fecha';

        // Crear mensaje
        $message = "La {$fieldLabel} de '{$task->title}' fue actualizada de {$oldDateFormatted} a {$newDateFormatted}";

        // Calcular prioridad según cercanía de la nueva fecha
        $priority = $this->calculateDateChangePriority($field, $oldDate, $newDate);

        // Crear notificación
        $notification = \App\Models\Notification::create([
            'user_id' => $task->assignee_id,
            'task_id' => $task->id,
            'flow_id' => $task->flow_id,
            'type' => 'task_date_changed',
            'title' => " Cambio de fecha: {$fieldLabel}",
            'message' => $message,
            'priority' => $priority,
            'is_read' => false,
            'data' => [
                'field' => $field,
                'field_label' => $fieldLabel,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'old_formatted' => $oldDateFormatted,
                'new_formatted' => $newDateFormatted,
                'changed_by_user_id' => auth()->id(),
                'changed_by_user_name' => auth()->user()?->name,
                'changed_at' => now()->toIso8601String(),
            ],
        ]);

        // Broadcast en tiempo real
        broadcast(new \App\Events\NotificationSent($notification))->toOthers();

        Log::info(' Notificación de cambio de fecha creada', [
            'notification_id' => $notification->id,
            'type' => 'task_date_changed',
            'priority' => $priority,
        ]);
    }

    /**
     * Calcular prioridad según qué tan cercana es la fecha
     */
    private function calculateDateChangePriority(string $field, $oldDate, $newDate): string
    {
        // Si la fecha es SLA, siempre es importante
        if ($field === 'sla_due_date') {
            return 'high';
        }

        // Si no hay nueva fecha, prioridad media
        if (!$newDate) {
            return 'medium';
        }

        try {
            $now = now();
            $date = \Carbon\Carbon::parse($newDate);
            $hoursUntil = $now->diffInHours($date, false);

            // Si la nueva fecha ya pasó o es muy cercana (< 24 horas)
            if ($hoursUntil < 24) {
                return 'urgent';
            }

            // Si la nueva fecha es dentro de 1 semana
            if ($hoursUntil < 168) { // 7 * 24 = 168 horas
                return 'high';
            }

            return 'medium';
        } catch (\Exception $e) {
            Log::error('Error calculando prioridad de cambio de fecha: ' . $e->getMessage());
            return 'medium';
        }
    }
}