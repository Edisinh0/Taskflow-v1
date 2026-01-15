<?php

namespace App\Observers;

use App\Models\Flow;
use App\Models\User;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class FlowObserver
{
    /**
     * Handle the Flow "saving" event.
     */
    public function saving(Flow $flow): void
    {
        if (auth()->check()) {
            $flow->last_updated_by = auth()->id();
        }
    }

    /**
     * Handle the Flow "created" event.
     */
    public function created(Flow $flow): void
    {
        // Si el flujo fue creado con un responsable, notificar
        if ($flow->responsible_id) {
            Log::info('📬 Notificando asignación de flujo', [
                'flow_id' => $flow->id,
                'flow_name' => $flow->name,
                'responsible_id' => $flow->responsible_id
            ]);

            try {
                NotificationService::flowAssigned($flow);
            } catch (\Throwable $e) {
                Log::error('Error al crear notificación de flujo asignado: ' . $e->getMessage());
                // No bloquear la creación del flujo por error en notificación
            }
        }
    }

    /**
     * Handle the Flow "updating" event.
     */
    public function updating(Flow $flow): void
    {
        // 1. Detectar si cambió el responsable
        if ($flow->isDirty('responsible_id')) {
            $oldResponsibleId = $flow->getOriginal('responsible_id');
            $newResponsibleId = $flow->responsible_id;

            // Solo notificar si hay un nuevo responsable
            if ($newResponsibleId) {
                Log::info('📬 Responsable de flujo cambió', [
                    'flow_id' => $flow->id,
                    'old_responsible_id' => $oldResponsibleId,
                    'new_responsible_id' => $newResponsibleId
                ]);

                try {
                    $oldResponsible = $oldResponsibleId
                        ? User::find($oldResponsibleId)
                        : null;

                    $newResponsible = User::find($newResponsibleId);

                    if ($newResponsible) {
                        NotificationService::flowResponsibleChanged(
                            $flow,
                            $oldResponsible,
                            $newResponsible
                        );
                    }
                } catch (\Throwable $e) {
                    Log::error('Error al cambiar responsable del flujo: ' . $e->getMessage());
                }
            }
        }

        // 2. Detectar si se marca como completado
        if ($flow->isDirty('status') && $flow->status === 'completed') {
            Log::info('📬 Flujo completado, notificando responsable', [
                'flow_id' => $flow->id,
                'flow_name' => $flow->name
            ]);

            try {
                NotificationService::flowCompleted($flow);
            } catch (\Throwable $e) {
                Log::error('Error al notificar flujo completado: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle the Flow "deleted" event.
     * Soft-delete all tasks associated with the flow.
     */
    public function deleted(Flow $flow): void
    {
        Log::info('🗑️ Flow eliminado, eliminando tareas en cascada', ['flow_id' => $flow->id]);
        
        // Soft delete de todas las tareas asociadas
        $flow->tasks()->delete();
    }

    /**
     * Handle the Flow "restored" event.
     * Restore all tasks associated with the flow.
     */
    public function restored(Flow $flow): void
    {
        Log::info('♻️ Flow restaurado, restaurando tareas', ['flow_id' => $flow->id]);
        
        // Restaurar tareas (asumiendo que fueron borradas al mismo tiempo)
        // Nota: Esto restaurará TODAS las tareas borradas del flujo, incluso las que 
        // se borraron individualmente antes de borrar el flujo. 
        // Para una implementación más precisa se necesitaría tracking de fecha de borrado,
        // pero para este caso de uso es suficiente.
        $flow->tasks()->restore();
    }
}
