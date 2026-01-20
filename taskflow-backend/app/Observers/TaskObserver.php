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

            // IMPORTANTE: Si la tarea está bloqueada, cambiar el status a 'blocked'
            if ($task->is_blocked && !in_array($task->status, ['completed', 'cancelled'])) {
                $task->status = 'blocked';
                $task->blocked_reason = 'Esperando tareas precedentes';
                Log::info("🔒 Tarea creada con status 'blocked' por dependencias", [
                    'title' => $task->title,
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

            Log::info('🔄 [UPDATED] Cambio de asignado detectado', [
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

        // 1. Solo actuamos si el estado cambió A 'completed'
        if ($task->isDirty('status') && $task->status === 'completed') {
            Log::info('✅ Tarea completada, liberando dependientes', [
                'task_id' => $task->id,
                'title' => $task->title,
            ]);

            // 🔔 Generar notificación de tarea completada
            NotificationService::taskCompleted($task);

            // 🔔 Si es milestone, generar notificación especial
            if ($task->is_milestone) {
                NotificationService::milestoneCompleted($task);
            }

            // 2. Buscar tareas que dependían de esta (como tarea precedente)
            $taskDependents = Task::where('depends_on_task_id', $task->id)->get();
            Log::info("📊 Encontradas {$taskDependents->count()} tareas dependientes (depends_on_task_id)");

            foreach ($taskDependents as $dependent) {
                Log::info("🔍 Procesando tarea dependiente {$dependent->id}: {$dependent->title}");
                $this->checkAndUnlock($dependent);
            }

            // 3. Buscar tareas que dependían de esta (como milestone)
            $milestoneDependents = Task::where('depends_on_milestone_id', $task->id)->get();
            Log::info("📊 Encontradas {$milestoneDependents->count()} tareas dependientes (depends_on_milestone_id)");

            foreach ($milestoneDependents as $dependent) {
                Log::info("🔍 Procesando tarea dependiente de milestone: {$dependent->id}");
                $this->checkAndUnlock($dependent);
            }
        }
        
        // 4. Lógica de Re-bloqueo: Si se reabre una tarea completada
        if ($task->isDirty('status') && 
            $task->status !== 'completed' && 
            $task->getOriginal('status') === 'completed') {
            
            Log::warning("⚠️ Tarea {$task->id} reabierta. Re-bloqueando dependientes.");
            
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

            Log::info("📊 Progreso del flujo actualizado (basado en root tasks): {$flow->id} -> {$avgProgress}% ({$flow->status})");

        } catch (\Exception $e) {
            Log::error("❌ Error actualizando progreso del flujo {$task->flow_id}: " . $e->getMessage());
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
                Log::info("📊 Progreso del Milestone actualizado: {$parent->id} -> {$newProgress}% ({$parent->status})");
            }
            
        } catch (\Exception $e) {
            Log::error("❌ Error actualizando progreso del padre {$task->parent_task_id}: " . $e->getMessage());
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
                Log::info("⏸️ Tarea {$task->id} sigue bloqueada por tarea precedente {$parentTask->id}");
            }
        }
        
        // Verificar dependencia de Hito
        if ($task->depends_on_milestone_id) {
            $milestoneTask = Task::find($task->depends_on_milestone_id);
            if ($milestoneTask && $milestoneTask->status !== 'completed') {
                $canUnlock = false;
                Log::info("⏸️ Tarea {$task->id} sigue bloqueada por milestone {$milestoneTask->id}");
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
                Log::info("🔓 Tarea {$task->id} cambiada de 'blocked' a 'pending'");
            }

            // Si es una subtarea (tiene parent_task_id) y está en pending, cambiarla a in_progress
            if ($task->parent_task_id && $task->status === 'pending') {
                $updateData['status'] = 'in_progress';
                Log::info("🚀 Subtarea {$task->id} cambiada a 'in_progress' automáticamente");
            }

            $task->update($updateData);
            Log::info("🔓 Tarea {$task->id} desbloqueada completamente.", $updateData);
        }
    }
}