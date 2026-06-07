<?php

namespace App\Jobs;

use App\Events\AdminDashboardUpdated;
use App\Events\SendResultSolution;
use App\Events\SendNotification;
use App\Models\Notification;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\User;
use App\Service\MarkdownConverter;
use App\Service\Notification\NotificationType;
use App\Service\Task\CodeJudgeService;
use App\Service\Task\TaskStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSolution implements ShouldQueue
{
    use Queueable;

    public string $code;
    public int $taskId;
    public int $userId;
    public ?int $contestId;

    public function __construct(string $code, int $taskId, int $userId, ?int $contestId = null)
    {
        $this->code = $code;
        $this->taskId = $taskId;
        $this->userId = $userId;
        $this->contestId = $contestId;
        $this->onQueue(config('queue.names.solution_checks', 'solution-checks'));
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $task = Task::find($this->taskId);
        if (!$task) {
            $notification = $this->createNotifacation('The task was deleted', NotificationType::TASK_ERROR, $this->userId);
            $notification->save();

            event(new SendNotification($notification, $this->taskId));

            return;
        }

        try {
            $result = app(CodeJudgeService::class)->run($task, $this->code);
        } catch (Exception $ex) {
            $attempt = $this->createAttempt(
                $this->userId,
                $this->taskId,
                TaskStatus::OTHER_ERROR,
                $ex->getMessage(),
                null,
                null,
                $this->code,
                $this->contestId
            );
            $attempt->save();

            $notification = $this->createNotifacation(
                $ex->getMessage(),
                NotificationType::TASK_ERROR,
                $this->userId
            );
            $notification->save();

            event(new SendResultSolution($attempt, $notification->id));
            event(new SendNotification($notification, $this->taskId));

            return;
        }

        $attempt = $this->createAttempt(
            $this->userId,
            $this->taskId,
            $result->status,
            $result->description,
            $result->executionTimeS,
            $result->peakMemoryUsageMb,
            $this->code,
            $this->contestId
        );
        $attempt->save();

        $notificationType = $result->isAccepted()
            ? NotificationType::TASK_SUCCESS
            : NotificationType::TASK_ERROR;

        $message = $result->isAccepted()
            ? "[$task->title](/task/solution/{$this->taskId}) - Задача выполнена"
            : "[Перейти к задаче](/task/solution/{$this->taskId}) - {$result->description}";

        $notification = $this->createNotifacation(
            MarkdownConverter::convertToSimpleHtml($message),
            $notificationType,
            $this->userId
        );
        $notification->save();

        event(new SendResultSolution($attempt, $notification->id));
        event(new SendNotification($notification, $this->taskId));
        event(new AdminDashboardUpdated(
            $result->isAccepted() ? 'success' : 'attempt',
            $result->isAccepted() ? 'Accepted' : 'Новая попытка',
            "{$user->name} отправил решение задачи «{$task->title}»",
            [
                'users' => User::count(),
                'tasks' => Task::count(),
                'attempts_today' => Attempt::whereDate('created_at', today())->count(),
                'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
            ],
            [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'user_name' => $user->name,
                'status' => $result->status->value,
                'execution_time_s' => $result->executionTimeS,
                'peak_memory_usage_mb' => $result->peakMemoryUsageMb,
            ],
        ));
    }

    protected function createNotifacation(string $content, NotificationType $type, int $userId): Notification
    {
        $notifacation = new Notification();
        $notifacation->receiver_id = $userId;
        $notifacation->type = $type;
        $notifacation->content = $content;

        return $notifacation;
    }

    protected function createAttempt(
        int $userId,
        int $taskId,
        TaskStatus $status,
        string $description,
        ?float $executonTime = null,
        ?int $executionMemory = null,
        ?string $code = null,
        ?int $contestId = null
    ): Attempt {
        $attempt = new Attempt();
        $attempt->user_id = $userId;
        $attempt->task_id = $taskId;
        $attempt->contest_id = $contestId;
        $attempt->status = $status;
        $attempt->peak_memory_usage_mb = $executionMemory;
        $attempt->execution_time_s = $executonTime;
        $attempt->description = $description;
        $attempt->code = $code;

        return $attempt;
    }
}
