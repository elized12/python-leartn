<?php

namespace App\Jobs;

use App\Models\Notifacation;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\Task\Test;
use App\Models\User;
use App\Service\Message\MessageType;
use App\Service\Task\TaskStatus;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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
            $notifaction = new Notifacation();
            $notifaction->receiver_id = $this->userId;
            $notifaction->type = MessageType::ERROR;
            $notifaction->message = 'The task was deleted';
            $notifaction->save();

            return;
        }

        $fileName = 'app' . $this->taskId . '_' . $this->userId . now() . '.py';
        if (!Storage::put("/solution/$fileName", $this->code)) {
            $notifaction = new Notifacation();
            $notifaction->receiver_id = $this->userId;
            $notifaction->type = MessageType::ERROR;
            $notifaction->message = 'Server error, try again later';
            $notifaction->save();

            return;
        }

        $memoryLimit = $task->memory_limit_b ? $task->memory_limit_b . 'b' : '128mb';
        $timeLimit = $task->time_limit_s ? $task->time_limit_s . 's' : '15s';

        $scriptPath = storage_path("app/solution/{$fileName}");

        $process = new Process([
            'docker',
            'run',
            '--rm',
            '-i',
            '-v',
            "$scriptPath:/app/main.py",
            "--memory=$memoryLimit",
            "--memory-swap=$memoryLimit",
            '--cplus=0.5',
            '--pids-limit=64',
            '--network=none',
            'python-sandbox:latest',
            'timeout',
            $timeLimit,
            'python3',
            '/app/main.py',
        ]);

        $taskTests = Test::where('task_id', '=', $task->id)
            ->orderBy('number', 'asc')
            ->get();


        $attemept = new Attempt();
        $attemept->user_id = $user->id;
        $attemept->task_id = $task->id;

        foreach ($taskTests as $test) {
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
                'python-sandbox:latest',
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

                switch ($exitCode) {
                    case CheckSolution::EXIT_CODE_NORMAL:
                        if ($output !== $expected) {
                            $attemept->status = TaskStatus::INCORRECT_RESULT;
                            $attemept->save();
                            return;
                        }
                        break;
                    case CheckSolution::EXIT_CODE_TIME_LIMIT:
                        $attemept->status = TaskStatus::TIME_LIMIT;
                        $attemept->save();
                        return;
                    case CheckSolution::EXIT_CODE_MEMORY_LIMIT:
                        $attemept->status = TaskStatus::MEMORY_LIMIT;
                        $attemept->save();
                        return;
                }
            } catch (Exception $ex) {
                Log::error("Error during execution: " . $ex->getMessage());
                break;
            }
        }
    }

    protected function processTest(string $scriptPath, ?int $memoryLimit, ?int $timeLimit, Test $test): bool
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
            '--cplus=0.5',
            '--pids-limit=64',
            '--network=none',
            'python-sandbox:latest',
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


            if ($this->isFailedTest($exitCode, $output, $expected)) {
                $attempt = $this->createAttempt($exitCode, $this->userId, $this->taskId);
                $attempt->save();
            }
            return true;
        } catch (Exception $ex) {
            Log::error("Error during execution: " . $ex->getMessage());
            return false;
        }
    }

    protected function isFailedTest(int $exitCode, ?string $output, ?string $expected): bool
    {
        return $exitCode != 0 || $output != $expected;
    }

    protected function createAttempt(int $exitCode, int $userId, int $taskId): Attempt
    {
        $attempt = new Attempt();
        $attempt->user_id = $userId;
        $attempt->task_id = $taskId;

        switch ($exitCode) {
            case CheckSolution::EXIT_CODE_MEMORY_LIMIT:
                $attempt->status = TaskStatus::MEMORY_LIMIT;
                break;
            case CheckSolution::EXIT_CODE_TIME_LIMIT:
                $attempt->status = TaskStatus::TIME_LIMIT;
                break;
            case CheckSolution::EXIT_CODE_NORMAL:
                $attempt->status = TaskStatus::INCORRECT_RESULT;
                break;
            default:
                $attempt->status = TaskStatus::OTHER_ERROR;
                break;
        }
        return $attempt;
    }

    protected function createNotifacation(string $message, MessageType $type, int $userId): Notifacation
    {
        $notifacation = new Notifacation();
        $notifacation->receiver_id = $userId;
        $notifacation->type = $type;
        $notifacation->message = $message;

        return $notifacation;
    }
}
