<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaBreached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $escalated;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, bool $escalated = false)
    {
        $this->task = $task->load(['assignee', 'flow']);
        $this->escalated = $escalated;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('task.' . $this->task->id),
            new PrivateChannel('flow.' . $this->task->flow_id),
        ];

        // Notificar al assignee
        if ($this->task->assignee_id) {
            $channels[] = new PrivateChannel('user.' . $this->task->assignee_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return $this->escalated ? 'sla.escalated' : 'sla.breached';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'priority' => $this->task->priority,
                'assignee' => $this->task->assignee ? [
                    'id' => $this->task->assignee->id,
                    'name' => $this->task->assignee->name,
                ] : null,
                'sla_due_date' => $this->task->sla_due_date?->toIso8601String(),
                'sla_days_overdue' => $this->task->sla_days_overdue,
                'sla_breach_at' => $this->task->sla_breach_at?->toIso8601String(),
            ],
            'escalated' => $this->escalated,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
