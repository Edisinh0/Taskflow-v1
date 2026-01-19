<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification->load(['task', 'flow']);

        \Log::info('🔥 NotificationSent Event Constructor', [
            'notification_id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'title' => $notification->title,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = 'user.' . $this->notification->user_id;
        \Log::info('📡 NotificationSent broadcastOn called', [
            'channel' => $channel,
            'notification_id' => $this->notification->id,
        ]);

        return [
            new PrivateChannel($channel),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $data = [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type,
                'title' => $this->notification->title,
                'message' => $this->notification->message,
                'priority' => $this->notification->priority,
                'task_id' => $this->notification->task_id,
                'flow_id' => $this->notification->flow_id,
                'data' => $this->notification->data,
                'created_at' => $this->notification->created_at->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        \Log::info('📦 NotificationSent broadcastWith called', [
            'data' => $data,
        ]);

        return $data;
    }
}
