<?php

namespace App\Jobs;

use App\Events\SendAiHintChunk;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Service\Task\AiHintService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateAiHint implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        public int $taskId,
        public int $attemptId,
        public int $userId,
        public string $requestId
    ) {
        $this->onQueue(config('queue.names.ai_hints', 'ai-hints'));
    }

    public function handle(AiHintService $aiHintService): void
    {
        $task = Task::query()
            ->with(['categories', 'files'])
            ->find($this->taskId);

        $attempt = Attempt::query()
            ->where('id', $this->attemptId)
            ->where('task_id', $this->taskId)
            ->where('user_id', $this->userId)
            ->first();

        if (!$task || !$attempt) {
            $this->storeError('Не удалось найти задачу или попытку для подсказки.');
            return;
        }

        try {
            event($this->event('started'));

            $hint = '';
            $aiHintService->stream($task, $attempt, function (string $chunk) use (&$hint) {
                $hint .= $chunk;
                event($this->event('chunk', content: $chunk));
            });

            event($this->event('done'));
        } catch (Throwable $exception) {
            $this->storeError($exception->getMessage());
        }
    }

    private function storeError(string $message): void
    {
        event($this->event('error', message: $message));
    }

    public function failed(?Throwable $exception): void
    {
        $this->storeError($exception?->getMessage() ?? 'Не удалось сформировать ИИ-подсказку.');
    }

    private function event(string $state, ?string $content = null, ?string $message = null): SendAiHintChunk
    {
        return new SendAiHintChunk(
            $this->userId,
            $this->taskId,
            $this->attemptId,
            $this->requestId,
            $state,
            $content,
            $message
        );
    }
}
