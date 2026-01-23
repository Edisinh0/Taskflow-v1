<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se modifica una fecha de una tarea
 * Transmite via WebSocket (Reverb) para notificaciones en tiempo real
 */
class TaskDateChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $task;
    public $fieldName;      // 'estimated_end_at', etc.
    public $fieldLabel;     // "Fecha estimada de finalización"
    public $oldDate;
    public $newDate;
    public $changedByUser;

    /**
     * Create a new event instance.
     */
    public function __construct(
        Task $task,
        string $fieldName,
        $oldDate,
        $newDate
    ) {
        $this->task = $task;
        $this->fieldName = $fieldName;
        $this->fieldLabel = $this->getFieldLabel($fieldName);
        $this->oldDate = $oldDate;
        $this->newDate = $newDate;
        $this->changedByUser = auth()->user();
    }

    /**
     * Get the channels the event should broadcast on.
     * Canal privado del usuario asignado + canal público del flujo
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Canal privado del usuario asignado
        if ($this->task->assignee_id) {
            $channels[] = new PrivateChannel("users.{$this->task->assignee_id}");
        }

        // Canal público del flujo (para PMs/Admins que están viendo el flujo)
        if ($this->task->flow_id) {
            $channels[] = new Channel("flows.{$this->task->flow_id}");
        }

        return $channels;
    }

    /**
     * Nombre del evento para el cliente
     */
    public function broadcastAs(): string
    {
        return 'TaskDateChanged';
    }

    /**
     * Datos que se envían al cliente via WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'flow_id' => $this->task->flow_id,
            'field_name' => $this->fieldName,
            'field_label' => $this->fieldLabel,
            'old_date' => $this->oldDate ? \Carbon\Carbon::parse($this->oldDate)->format('d/m/Y H:i') : 'Sin fecha',
            'new_date' => $this->newDate ? \Carbon\Carbon::parse($this->newDate)->format('d/m/Y H:i') : 'Sin fecha',
            'old_date_iso' => $this->oldDate,
            'new_date_iso' => $this->newDate,
            'changed_by' => $this->changedByUser?->name,
            'changed_by_id' => $this->changedByUser?->id,
            'changed_at' => now()->toIso8601String(),
            'message' => "{$this->fieldLabel} cambió de " .
                         ($this->oldDate ? \Carbon\Carbon::parse($this->oldDate)->format('d/m/Y H:i') : 'Sin fecha') .
                         " a " .
                         ($this->newDate ? \Carbon\Carbon::parse($this->newDate)->format('d/m/Y H:i') : 'Sin fecha'),
        ];
    }

    /**
     * Obtener label legible del campo
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'estimated_start_at' => 'Fecha estimada de inicio',
            'estimated_end_at' => 'Fecha estimada de finalización',
            'actual_start_at' => 'Fecha real de inicio',
            'actual_end_at' => 'Fecha real de finalización',
            'sla_due_date' => 'Fecha de vencimiento SLA',
            'milestone_target_date' => 'Fecha objetivo del milestone',
        ];

        return $labels[$field] ?? $field;
    }
}
