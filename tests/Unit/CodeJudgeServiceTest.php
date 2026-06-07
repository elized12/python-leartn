<?php

namespace Tests\Unit;

use App\Service\Task\CodeJudgeService;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CodeJudgeServiceTest extends TestCase
{
    public function test_extract_cpu_time_uses_elapsed_time_marker(): void
    {
        $service = new CodeJudgeService();
        $process = new Process(['php', '-r', 'echo "";']);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractCpuTimeS');

        $process = new Process(['true']);
        $process->run();

        $errorOutput = "__JUDGE_USER_TIME_S__:0.25\n" .
            "__JUDGE_SYSTEM_TIME_S__:0.15\n" .
            "__JUDGE_ELAPSED_TIME_S__:1.40\n";

        $method->setAccessible(true);

        $this->assertSame(1.4, $method->invoke($service, $errorOutput));
    }

    public function test_runner_wrapper_marks_sigkill_as_memory_limit(): void
    {
        $service = new CodeJudgeService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('runnerWrapperCode');
        $method->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/code-judge-service-test-' . uniqid('', true);
        mkdir($tempDir, 0777, true);

        $wrapperPath = $tempDir . '/judge_runner.py';
        $killerPath = $tempDir . '/killer.py';

        file_put_contents($wrapperPath, $method->invoke($service));
        file_put_contents($killerPath, "import os\nimport signal\nos.kill(os.getpid(), signal.SIGKILL)\n");

        $process = new Process([
            'python3',
            $wrapperPath,
            '1',
            '1048576',
            $killerPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        $this->assertSame(137, $process->getExitCode());

        @unlink($killerPath);
        @unlink($wrapperPath);
        @rmdir($tempDir);
    }
}
