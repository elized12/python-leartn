<?php

namespace App\Events;

use App\Models\Task\Attempt;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendResultSolution implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Attempt $attempt,
        public ?int $notificationId = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.task.' . $this->attempt->user_id)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id'      => $this->attempt->id,
            'task_id' => $this->attempt->task_id,
            'description' => $this->attempt->description,
            'status'    => $this->attempt->status->value,
            'execution_time_s' => $this->attempt->execution_time_s,
            'peak_memory_usage_mb' => $this->attempt->peak_memory_usage_mb,
            'notification_id' => $this->notificationId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.task';
    }
}
