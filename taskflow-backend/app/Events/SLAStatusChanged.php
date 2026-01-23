<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SLAStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $task;
    public $oldStatus;  // 'none', 'warning', 'critical'
    public $newStatus;  // 'none', 'warning', 'critical'

    /**
     * Create a new event instance.
     *
     * @param Task $task
     * @param string $oldStatus
     * @param string $newStatus
     */
    public function __construct(Task $task, string $oldStatus, string $newStatus)
    {
        $this->task = $task;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Canal del usuario asignado
        if ($this->task->assignee_id) {
            $channels[] = new PrivateChannel("user.{$this->task->assignee_id}");
        }

        // Canal del flujo (para que otros vean cambios en tareas del flujo)
        if ($this->task->flow_id) {
            $channels[] = new Channel("flow.{$this->task->flow_id}");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'SLAStatusChanged';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'flow_id' => $this->task->flow_id,
            'assignee_id' => $this->task->assignee_id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'sla_due_date' => $this->task->sla_due_date?->toIso8601String(),
            'estimated_end_at' => $this->task->estimated_end_at?->toIso8601String(),
            'message' => $this->generateMessage(),
        ];
    }

    /**
     * Generate a human-readable message for the SLA status change
     *
     * @return string
     */
    private function generateMessage(): string
    {
        if ($this->newStatus === 'none') {
            return "Alerta de SLA resuelta para '{$this->task->title}'";
        }

        if ($this->newStatus === 'critical') {
            return "CRÍTICA: '{$this->task->title}' está en SLA crítico (+48h de retraso)";
        }

        return "'{$this->task->title}' está en SLA advertencia (+24h de retraso)";
    }
}
