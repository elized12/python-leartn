<?php

namespace Tests\Unit;

use App\Service\KnowledgeTracingService;
use Tests\TestCase;

class KnowledgeTracingFormulaTest extends TestCase
{
    public function test_incorrect_answer_decreases_mastery_probability(): void
    {
        $probability = app(KnowledgeTracingService::class)->calculateProbability(
            0.30,
            false,
            $this->settings(),
            900
        );

        $this->assertLessThan(0.30, $probability);
    }

    public function test_easy_task_cannot_raise_mastery_above_easy_cap(): void
    {
        $probability = app(KnowledgeTracingService::class)->calculateProbability(
            0.95,
            true,
            $this->settings(),
            900
        );

        $this->assertSame(0.70, $probability);
    }

    public function test_medium_task_cannot_raise_mastery_above_medium_cap(): void
    {
        $probability = app(KnowledgeTracingService::class)->calculateProbability(
            0.95,
            true,
            $this->settings(),
            1500
        );

        $this->assertSame(0.85, $probability);
    }

    public function test_hard_task_can_raise_mastery_above_medium_cap(): void
    {
        $probability = app(KnowledgeTracingService::class)->calculateProbability(
            0.80,
            true,
            $this->settings(),
            2000
        );

        $this->assertGreaterThan(0.85, $probability);
    }

    private function settings(): array
    {
        return [
            'prior' => 0.30,
            'learn' => 0.10,
            'guess' => 0.20,
            'slip' => 0.10,
            'easy_rating_max' => 1199,
            'medium_rating_max' => 1799,
            'easy_mastery_cap' => 0.70,
            'medium_mastery_cap' => 0.85,
            'hard_mastery_cap' => 1.00,
        ];
    }
}
