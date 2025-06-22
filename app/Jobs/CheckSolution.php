<?php

namespace App\Jobs;

use App\Events\AttemptNotification;
use App\Models\Notification;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\Task\Test;
use App\Models\User;
use App\Service\Message\MessageType;
use App\Service\Task\TaskStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CheckSolution implements ShouldQueue
{
    use Queueable;

    public const EXIT_CODE_TIME_LIMIT = 124;
    public const EXIT_CODE_MEMORY_LIMIT = 137;
    public const EXIT_CODE_NORMAL = 0;

    public string $code;
    public int $taskId;
    public int $userId;

    public function __construct(string $code, int $taskId, int $userId)
    {
        $this->code = $code;
        $this->taskId = $taskId;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $task = Task::find($this->taskId);
        if (!$task) {
            $notification = $this->createNotifacation('The task was deleted', MessageType::ERROR, $this->userId);
            $notification->save();

            event(new AttemptNotification($notification));

            return;
        }

        $timestamp = now()->format('Ymd_His');
        $fileName = 'app' . $this->taskId . '_' . $this->userId . '_' . $timestamp . '.py';
        if (!Storage::put("/solution/$fileName", $this->code)) {
            $notification = $this->createNotifacation('Server error, try again later', MessageType::ERROR, $this->userId);
            $notification->save();

            event(new AttemptNotification($notification));

            return;
        }

        $memoryLimit = $task->memory_limit_b ? $task->memory_limit_b . 'b' : '128mb';
        $timeLimit = $task->time_limit_s ? $task->time_limit_s . 's' : '15s';

        $scriptPath = Storage::path("/solution/{$fileName}");

        $taskTests = Test::where('task_id', '=', $task->id)
            ->orderBy('number', 'asc')
            ->get();

        foreach ($taskTests as $test) {
            if (!$this->processTest(
                $scriptPath,
                $memoryLimit,
                $timeLimit,
                $test
            )) {
                Storage::delete("/solution/{$fileName}");
                return;
            }
        }

        $successAttempt = new Attempt(
            [
                'user_id' => $this->userId,
                'task_id' => $this->taskId,
                'status' => TaskStatus::COMPLETED,
                'description' => 'Attempt Complete'
            ]
        );
        $successAttempt->save();

        $notification = $this->createNotifacation(
            "task {$task->title} completed",
            MessageType::SUCCESS,
            $this->userId
        );
        $notification->save();
        event(new AttemptNotification($notification));

        Storage::delete("/solution/{$fileName}");
    }

    protected function processTest(string $scriptPath, string $memoryLimit, string $timeLimit, Test $test): bool
    {
        $process = new Process([
            'docker',
            'run',
            '--rm',
            '-i',
            '-v',
            "$scriptPath:/app/main.py",
            "--memory=$memoryLimit",
            "--memory-swap=$memoryLimit",
            '--cpus=0.5',
            '--pids-limit=64',
            '--network=none',
            'python:latest',
            'timeout',
            $timeLimit,
            'python3',
            '/app/main.py',
        ]);

        $process->setInput($test->input);

        try {
            $exitCode = $process->run();

            $output = trim($process->getOutput());
            $expected = trim($test->expected_output);
            $errorOutput = trim($process->getErrorOutput());

            if ($this->isFailedTest($exitCode, $output, $expected)) {
                switch ($exitCode) {
                    case CheckSolution::EXIT_CODE_MEMORY_LIMIT:
                        $notification = $this->createNotifacation(
                            'task not completed : memory limit',
                            MessageType::ERROR,
                            $this->userId
                        );
                        $attempt = $this->createAttempt(
                            $this->userId,
                            $this->taskId,
                            $this->getErrorAttemptStatus($exitCode),
                            'task not completed : memory limit',
                        );
                        break;

                    case CheckSolution::EXIT_CODE_TIME_LIMIT:
                        $notification = $this->createNotifacation(
                            'task not completed : time limit',
                            MessageType::ERROR,
                            $this->userId
                        );
                        $attempt = $this->createAttempt(
                            $this->userId,
                            $this->taskId,
                            $this->getErrorAttemptStatus($exitCode),
                            'task not completed : time limit',
                        );
                        break;

                    case CheckSolution::EXIT_CODE_NORMAL:
                        $notification = $this->createNotifacation(
                            "task not completed : answer inncorect in test number {$test->number}",
                            MessageType::ERROR,
                            $this->userId
                        );
                        $attempt = $this->createAttempt(
                            $this->userId,
                            $this->taskId,
                            $this->getErrorAttemptStatus($exitCode),
                            "task not completed : answer inncorect in test number {$test->number}",
                        );
                        break;
                    default:
                        $notification = $this->createNotifacation(
                            $errorOutput,
                            MessageType::ERROR,
                            $this->userId
                        );
                        $attempt = $this->createAttempt(
                            $this->userId,
                            $this->taskId,
                            $this->getErrorAttemptStatus($exitCode),
                            $errorOutput,
                        );
                        break;
                }

                $notification->save();
                $attempt->save();

                event(new AttemptNotification($notification));

                return false;
            }

            return true;
        } catch (Exception $ex) {
            $notification = $this->createNotifacation(
                $ex->getMessage(),
                MessageType::SUCCESS,
                $this->userId
            );
            $notification->save();

            event(new AttemptNotification($notification));

            return false;
        }
    }

    protected function isFailedTest(int $exitCode, ?string $output, ?string $expected): bool
    {
        return $exitCode != 0 || $output != $expected;
    }

    protected function createAttempt(
        int $userId,
        int $taskId,
        TaskStatus $status,
        string $description,
        ?float $executonTime = null,
        ?int $executionMemory = null
    ): Attempt {
        $attempt = new Attempt();
        $attempt->user_id = $userId;
        $attempt->task_id = $taskId;
        $attempt->status = $status;
        $attempt->peak_memory_usage_b = $executionMemory;
        $attempt->execution_time_s = $executonTime;
        $attempt->description = $description;

        return $attempt;
    }

    protected function getErrorAttemptStatus(int $exitCode): TaskStatus
    {
        switch ($exitCode) {
            case CheckSolution::EXIT_CODE_MEMORY_LIMIT:
                return TaskStatus::MEMORY_LIMIT;
            case CheckSolution::EXIT_CODE_TIME_LIMIT:
                return TaskStatus::TIME_LIMIT;
            case CheckSolution::EXIT_CODE_NORMAL:
                return TaskStatus::INCORRECT_RESULT;
            default:
                return TaskStatus::OTHER_ERROR;
        }
    }

    protected function createNotifacation(string $message, MessageType $type, int $userId): Notification
    {
        $notifacation = new Notification();
        $notifacation->receiver_id = $userId;
        $notifacation->type = $type;
        $notifacation->message = $message;

        return $notifacation;
    }
}
