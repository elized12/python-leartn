<?php

namespace App\Service\Task;

class JudgeRunResult
{
    public function __construct(
        public readonly TaskStatus $status,
        public readonly string $description,
        public readonly ?int $failedTestNumber = null,
        public readonly ?float $executionTimeS = null,
        public readonly ?float $peakMemoryUsageMb = null,
    ) {}

    public function isAccepted(): bool
    {
        return $this->status === TaskStatus::COMPLETED;
    }
}
