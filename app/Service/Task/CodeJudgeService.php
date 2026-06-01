<?php

namespace App\Service\Task;

use App\Models\Task\File as TaskFile;
use App\Models\Task\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class CodeJudgeService
{
    public const EXIT_CODE_TIME_LIMIT = 124;
    public const EXIT_CODE_MEMORY_LIMIT = 137;
    public const EXIT_CODE_NORMAL = 0;

    public function run(Task $task, string $code): JudgeRunResult
    {
        $task->loadMissing(['environment', 'files']);

        if (!$task->environment || !$task->environment->is_active) {
            return new JudgeRunResult(TaskStatus::OTHER_ERROR, 'Окружение выполнения недоступно');
        }

        $tests = $this->normalizeTests($task);
        if (empty($tests)) {
            return new JudgeRunResult(TaskStatus::OTHER_ERROR, 'Для задачи не настроены тесты');
        }

        $workspace = 'judge/runs/' . Str::uuid()->toString();
        Storage::makeDirectory($workspace);

        try {
            Storage::put("$workspace/solution.py", $code);
            Storage::put("$workspace/judge_runner.py", $this->runnerWrapperCode());
            $this->copySupportFile($task->runner_file_path, "$workspace/runner.py");
            $this->copySupportFile($task->checker_file_path, "$workspace/checker.py");
            $peakMemoryUsageMb = null;
            $maxExecutionTimeS = 0.0;

            foreach ($tests as $index => $test) {
                $testNumber = (int) ($test['number'] ?? $index + 1);
                $this->prepareTaskFiles($task, $workspace);
                $this->prepareTestFiles($task, $workspace, $test);

                $input = (string) ($test['input'] ?? '');
                $expected = (string) ($test['expected'] ?? $test['expected_output'] ?? '');
                Storage::put("$workspace/input.txt", $input);
                Storage::put("$workspace/expected.txt", $expected);

                $solutionRun = $this->runPython(
                    $task,
                    $workspace,
                    $task->runner_file_path ? 'runner.py' : 'solution.py',
                    $input,
                    (float) ($task->time_limit_s ?: 15)
                );
                $solutionProcess = $solutionRun['process'];
                $executionTimeS = $this->extractCpuTimeS($solutionProcess) ?? 0.0;
                $maxExecutionTimeS = max($maxExecutionTimeS, $executionTimeS);
                $peakMemoryUsageMb = max(
                    $peakMemoryUsageMb ?? 0,
                    $solutionRun['peak_memory_usage_mb'] ?? 0,
                    $this->extractPeakMemoryUsageMb($solutionProcess) ?? 0
                ) ?: $peakMemoryUsageMb;

                $status = $this->statusFromExitCode($solutionProcess->getExitCode() ?? 1);
                if ($status !== TaskStatus::COMPLETED) {
                    return new JudgeRunResult(
                        $status,
                        $this->buildRuntimeDescription($status, $testNumber, $this->cleanProcessErrorOutput($solutionProcess)),
                        $testNumber,
                        $executionTimeS,
                        $peakMemoryUsageMb
                    );
                }

                $output = $solutionProcess->getOutput();
                Storage::put("$workspace/output.txt", $output);

                $checkerResult = $task->checker_file_path
                    ? $this->runCustomChecker($task, $workspace)
                    : $this->runStandardChecker($expected, $output);

                if (!$checkerResult['accepted']) {
                    return new JudgeRunResult(
                        TaskStatus::INCORRECT_RESULT,
                        $checkerResult['message'] ?: "Неправильный ответ на тесте {$testNumber}",
                        $testNumber,
                        $executionTimeS,
                        $peakMemoryUsageMb
                    );
                }
            }

            return new JudgeRunResult(
                TaskStatus::COMPLETED,
                'Задача выполнена',
                executionTimeS: round($maxExecutionTimeS, 3),
                peakMemoryUsageMb: $peakMemoryUsageMb,
            );
        } finally {
            Storage::deleteDirectory($workspace);
        }
    }

    private function normalizeTests(Task $task): array
    {
        if (is_array($task->tests) && isset($task->tests['tests']) && is_array($task->tests['tests'])) {
            return $task->tests['tests'];
        }

        if (is_array($task->tests) && array_is_list($task->tests)) {
            return $task->tests;
        }

        return $task->testCases()
            ->get()
            ->map(fn($test) => [
                'number' => $test->number,
                'input' => $test->input,
                'expected' => $test->expected_output,
            ])
            ->all();
    }

    private function copySupportFile(?string $sourcePath, string $targetPath): void
    {
        if (!$sourcePath) {
            return;
        }

        if (!Storage::exists($sourcePath)) {
            throw new RuntimeException("Файл {$sourcePath} не найден");
        }

        Storage::put($targetPath, Storage::get($sourcePath));
    }

    private function prepareTaskFiles(Task $task, string $workspace): void
    {
        foreach ($task->files as $file) {
            if (!$file instanceof TaskFile || !Storage::exists($file->file_path)) {
                continue;
            }

            $fileName = basename($file->file_path);
            $fileContent = Storage::get($file->file_path);

            Storage::put("{$workspace}/files/{$fileName}", $fileContent);

            if (!$this->isReservedWorkspaceFile($fileName)) {
                Storage::put("{$workspace}/{$fileName}", $fileContent);
            }
        }
    }

    private function prepareTestFiles(Task $task, string $workspace, array $test): void
    {
        $files = $test['files'] ?? [];
        if (!is_array($files) || empty($files)) {
            return;
        }

        $availableFiles = $task->files
            ->mapWithKeys(fn(TaskFile $file) => [basename($file->file_path) => $file->file_path]);

        foreach ($files as $file) {
            $name = $file['name'] ?? null;
            $target = $this->safeRelativePath($file['target'] ?? $name);

            if (!$name || !$target || !isset($availableFiles[$name])) {
                throw new RuntimeException("Файл теста {$name} не найден");
            }

            Storage::put("$workspace/$target", Storage::get($availableFiles[$name]));
        }
    }

    private function isReservedWorkspaceFile(string $fileName): bool
    {
        return in_array($fileName, [
            'solution.py',
            'runner.py',
            'checker.py',
            'judge_runner.py',
            'input.txt',
            'expected.txt',
            'output.txt',
        ], true);
    }

    private function runPython(
        Task $task,
        string $workspace,
        string $entrypoint,
        string $input,
        float $timeLimitS,
        array $arguments = []
    ): array {
        $workspacePath = Storage::path($workspace);
        $this->makeWorkspaceWritable($workspacePath);

        $memoryLimit = max(64, (int) ($task->memory_limit_mb ?: 128)) . 'm';
        $cpuLimit = max(1, (int) ceil($timeLimitS));
        $wallTimeout = $this->wallTimeoutForCpuLimit($cpuLimit);
        $containerName = 'judge_' . str_replace('-', '', Str::uuid()->toString());
        $dockerUser = $this->dockerUser();

        $command = [
            'docker',
            'run',
            '--name',
            $containerName,
            '--rm',
            '-i',
            "--memory={$memoryLimit}",
            "--memory-swap={$memoryLimit}",
            '--cpus=1',
            '--pids-limit=64',
            '--network=none',
            '--cap-drop=ALL',
            '--security-opt=no-new-privileges',
        ];

        array_push($command, ...$this->dockerWorkspaceOptions($workspacePath));

        if ($dockerUser) {
            $command[] = '--user';
            $command[] = $dockerUser;
        }

        array_push(
            $command,
            $task->environment->docker_image_name,
            '/usr/bin/time',
            '-f',
            "__JUDGE_USER_TIME_S__:%U\n__JUDGE_SYSTEM_TIME_S__:%S\n__JUDGE_ELAPSED_TIME_S__:%e\n__JUDGE_PEAK_MEMORY_KB__:%M",
            'python3',
            'judge_runner.py',
            (string) $cpuLimit,
            $entrypoint,
            ...$arguments,
        );

        $process = new Process($command);

        $process->setInput($input);
        $process->setTimeout($wallTimeout);
        $process->start();

        $peakMemoryUsageMb = null;
        while ($process->isRunning()) {
            $currentMemoryUsageMb = $this->readContainerMemoryUsageMb($containerName);
            if ($currentMemoryUsageMb !== null) {
                $peakMemoryUsageMb = max($peakMemoryUsageMb ?? 0, $currentMemoryUsageMb);
            }

            usleep(50_000);
        }

        $process->wait();

        return [
            'process' => $process,
            'peak_memory_usage_mb' => $peakMemoryUsageMb,
        ];
    }

    private function dockerUser(): ?string
    {
        $uid = config('judge.docker_uid');
        $gid = config('judge.docker_gid');

        if ($uid === null || $uid === '' || $gid === null || $gid === '') {
            return null;
        }

        return "{$uid}:{$gid}";
    }

    private function dockerWorkspaceOptions(string $workspacePath): array
    {
        $storageVolume = config('judge.storage_volume');

        if ($storageVolume && str_starts_with($workspacePath, '/var/www/html/storage/')) {
            return [
                '--mount',
                "type=volume,source={$storageVolume},target=/var/www/html/storage",
                '-w',
                $workspacePath,
            ];
        }

        return [
            '-v',
            "{$workspacePath}:/workspace",
            '-w',
            '/workspace',
        ];
    }

    private function wallTimeoutForCpuLimit(int $cpuLimit): int
    {
        $multiplier = max(1, (int) config('judge.wall_timeout_multiplier', 10));
        $grace = max(0, (int) config('judge.wall_timeout_grace_s', 10));
        $minimum = max(1, (int) config('judge.min_wall_timeout_s', 30));

        return max($minimum, ($cpuLimit * $multiplier) + $grace);
    }

    private function makeWorkspaceWritable(string $workspacePath): void
    {
        if (!is_dir($workspacePath)) {
            return;
        }

        $this->makePathTraversable($workspacePath);
        @chmod($workspacePath, 0777);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workspacePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            @chmod($item->getPathname(), $item->isDir() ? 0777 : 0666);
        }
    }

    private function makePathTraversable(string $workspacePath): void
    {
        $storageRoot = rtrim(Storage::path(''), DIRECTORY_SEPARATOR);
        $currentPath = rtrim($workspacePath, DIRECTORY_SEPARATOR);
        $paths = [];

        while (str_starts_with($currentPath, $storageRoot) && $currentPath !== $storageRoot) {
            $paths[] = $currentPath;
            $currentPath = dirname($currentPath);
        }

        $paths[] = $storageRoot;

        foreach (array_reverse($paths) as $path) {
            if (is_dir($path)) {
                @chmod($path, 0755);
            }
        }
    }

    private function readContainerMemoryUsageMb(string $containerName): ?int
    {
        $statsProcess = new Process([
            'docker',
            'stats',
            '--no-stream',
            '--format',
            '{{.MemUsage}}',
            $containerName,
        ]);
        $statsProcess->setTimeout(5);

        try {
            $statsProcess->run();
        } catch (ProcessTimedOutException) {
            Log::debug('Docker stats timed out while measuring memory usage', [
                'container' => $containerName,
            ]);

            return null;
        }

        if (!$statsProcess->isSuccessful()) {
            return null;
        }

        return $this->parseDockerMemoryUsageMb($statsProcess->getOutput());
    }

    private function parseDockerMemoryUsageMb(string $value): ?int
    {
        if (!preg_match('/([\d.]+)\s*([KMGT]?i?B)\s*\/?/i', trim($value), $matches)) {
            return null;
        }

        $amount = (float) $matches[1];
        $unit = strtolower($matches[2]);

        $mb = match ($unit) {
            'b' => $amount / 1024 / 1024,
            'kb', 'kib' => $amount / 1024,
            'mb', 'mib' => $amount,
            'gb', 'gib' => $amount * 1024,
            'tb', 'tib' => $amount * 1024 * 1024,
            default => null,
        };

        return $mb === null ? null : (int) ceil($mb);
    }

    private function extractPeakMemoryUsageMb(Process $process): ?int
    {
        if (!preg_match('/__JUDGE_PEAK_MEMORY_KB__:(\d+)/', $process->getErrorOutput(), $matches)) {
            return null;
        }

        return (int) ceil(((int) $matches[1]) / 1024);
    }

    private function extractCpuTimeS(Process $process): ?float
    {
        $errorOutput = $process->getErrorOutput();
        if (
            !preg_match('/__JUDGE_USER_TIME_S__:(\d+(?:\.\d+)?)/', $errorOutput, $userMatches)
            || !preg_match('/__JUDGE_SYSTEM_TIME_S__:(\d+(?:\.\d+)?)/', $errorOutput, $systemMatches)
        ) {
            return null;
        }

        return round((float) $userMatches[1] + (float) $systemMatches[1], 3);
    }

    private function cleanProcessErrorOutput(Process $process): string
    {
        return trim(preg_replace('/\R?__JUDGE_(?:PEAK_MEMORY_KB|USER_TIME_S|SYSTEM_TIME_S|ELAPSED_TIME_S)__:[^\r\n]*\R?/', '', $process->getErrorOutput()) ?? '');
    }

    private function runnerWrapperCode(): string
    {
        return <<<'PY'
import resource
import signal
import subprocess
import sys

cpu_limit = max(1, int(sys.argv[1]))
target = sys.argv[2]
exit_code = 1

def limit_cpu():
    resource.setrlimit(resource.RLIMIT_CPU, (cpu_limit, cpu_limit + 1))

try:
    completed = subprocess.run([sys.executable, target, *sys.argv[3:]], preexec_fn=limit_cpu)
    if completed.returncode in (-signal.SIGXCPU, -signal.SIGKILL):
        exit_code = 124
    else:
        exit_code = completed.returncode
finally:
    usage = resource.getrusage(resource.RUSAGE_CHILDREN).ru_maxrss
    usage = max(usage, resource.getrusage(resource.RUSAGE_SELF).ru_maxrss)
    print(f"__JUDGE_PEAK_MEMORY_KB__:{usage}", file=sys.stderr)

sys.exit(exit_code)
PY;
    }

    private function runCustomChecker(Task $task, string $workspace): array
    {
        $checkerRun = $this->runPython(
            $task,
            $workspace,
            'checker.py',
            '',
            (float) ($task->time_limit_s ?: 2),
            ['input.txt', 'expected.txt', 'output.txt']
        );
        $process = $checkerRun['process'];

        return [
            'accepted' => ($process->getExitCode() ?? 1) === self::EXIT_CODE_NORMAL,
            'message' => $this->cleanProcessErrorOutput($process) ?: trim($process->getOutput()),
        ];
    }

    private function runStandardChecker(string $expected, string $output): array
    {
        return [
            'accepted' => $this->tokenize($expected) === $this->tokenize($output),
            'message' => '',
        ];
    }

    private function safeRelativePath(?string $path): ?string
    {
        if (!$path || str_starts_with($path, '/') || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function tokenize(string $value): array
    {
        $value = trim($value);

        return $value === '' ? [] : preg_split('/\s+/', $value);
    }

    private function statusFromExitCode(int $exitCode): TaskStatus
    {
        return match ($exitCode) {
            self::EXIT_CODE_NORMAL => TaskStatus::COMPLETED,
            self::EXIT_CODE_TIME_LIMIT => TaskStatus::TIME_LIMIT,
            self::EXIT_CODE_MEMORY_LIMIT => TaskStatus::MEMORY_LIMIT,
            default => TaskStatus::OTHER_ERROR,
        };
    }

    private function buildRuntimeDescription(TaskStatus $status, int $testNumber, string $errorOutput): string
    {
        return match ($status) {
            TaskStatus::TIME_LIMIT => "Превышено время выполнения на тесте {$testNumber}",
            TaskStatus::MEMORY_LIMIT => "Превышен лимит памяти на тесте {$testNumber}",
            default => $errorOutput ?: "Ошибка выполнения на тесте {$testNumber}",
        };
    }
}
