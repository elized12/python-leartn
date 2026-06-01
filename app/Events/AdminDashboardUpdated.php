<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $message,
        public readonly array $stats = [],
        public readonly array $meta = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.dashboard'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'stats' => $this->stats,
            'meta' => $this->meta,
            'created_at' => now()->format('d.m.Y H:i'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.dashboard.updated';
    }
}
