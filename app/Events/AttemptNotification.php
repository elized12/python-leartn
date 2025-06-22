<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttemptNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.task.' . $this->notification->receiver_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'      => $this->notification->id,
            'message' => $this->notification->message,
            'type'    => $this->notification->type,
        ];
    }

    public function broadcastAs(): string
    {
        return 'attempt.notification';
    }
}
