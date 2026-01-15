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

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task, array $changes = [])
    {
        $this->task = $task->load(['assignee', 'flow']);
        $this->changes = $changes;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('task.' . $this->task->id),
            new PrivateChannel('flow.' . $this->task->flow_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.updated';
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
                'progress' => $this->task->progress,
                'assignee' => $this->task->assignee ? [
                    'id' => $this->task->assignee->id,
                    'name' => $this->task->assignee->name,
                ] : null,
                'sla_breached' => $this->task->sla_breached,
                'sla_days_overdue' => $this->task->sla_days_overdue,
                'updated_at' => $this->task->updated_at->toIso8601String(),
            ],
            'changes' => $this->changes,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
