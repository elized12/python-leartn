<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendAiHintChunk implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $taskId,
        public readonly int $attemptId,
        public readonly string $requestId,
        public readonly string $state,
        public readonly ?string $content = null,
        public readonly ?string $message = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.task.' . $this->userId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->taskId,
            'attempt_id' => $this->attemptId,
            'request_id' => $this->requestId,
            'state' => $this->state,
            'content' => $this->content,
            'message' => $this->message,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.ai-hint';
    }
}
