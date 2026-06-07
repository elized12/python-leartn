<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public ?int $taskId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.notification.' . $this->notification->receiver_id)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'      => $this->notification->id,
            'content' => $this->notification->content,
            'type'    => $this->notification->type->value,
            'task_id' => $this->taskId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.notification';
    }
}
