<?php

namespace Tests\Unit;

use App\Models\Task\Attempt;
use App\Models\Task\Category;
use App\Models\Task\Task;
use App\Models\User;
use App\Models\UserCategoryMastery;
use App\Service\KnowledgeTracingService;
use App\Service\Task\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class KnowledgeTracingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_attempt_increases_category_probability(): void
    {
        [$user, $task, $category] = $this->userTaskAndCategory();

        $attempt = $this->attempt($user, $task, TaskStatus::COMPLETED);

        app(KnowledgeTracingService::class)->updateFromAttempt($attempt);

        $mastery = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        $this->assertNotNull($mastery);
        $this->assertGreaterThan(0.3, $mastery->probability);
        $this->assertNotNull($attempt->fresh()->knowledge_traced_at);
    }

    public function test_incorrect_attempt_decreases_category_probability(): void
    {
        [$user, $task, $category] = $this->userTaskAndCategory();

        $attempt = $this->attempt($user, $task, TaskStatus::INCORRECT_RESULT);

        app(KnowledgeTracingService::class)->updateFromAttempt($attempt);

        $mastery = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->first();

        $this->assertNotNull($mastery);
        $this->assertLessThan(0.3, $mastery->probability);
        $this->assertGreaterThanOrEqual(0, $mastery->probability);
        $this->assertLessThanOrEqual(1, $mastery->probability);
    }

    public function test_attempts_change_probability_until_first_accepted_solution(): void
    {
        [$user, $task, $category] = $this->userTaskAndCategory();

        $firstAttempt = $this->attempt($user, $task, TaskStatus::INCORRECT_RESULT);
        app(KnowledgeTracingService::class)->updateFromAttempt($firstAttempt);

        $probabilityAfterFirstAttempt = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->value('probability');

        $secondAttempt = $this->attempt($user, $task, TaskStatus::INCORRECT_RESULT);
        app(KnowledgeTracingService::class)->updateFromAttempt($secondAttempt);

        $probabilityAfterSecondAttempt = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->value('probability');

        $this->assertLessThan($probabilityAfterFirstAttempt, $probabilityAfterSecondAttempt);
        $this->assertNotNull($secondAttempt->fresh()->knowledge_traced_at);

        $acceptedAttempt = $this->attempt($user, $task, TaskStatus::COMPLETED);
        app(KnowledgeTracingService::class)->updateFromAttempt($acceptedAttempt);

        $probabilityAfterAcceptedAttempt = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->value('probability');

        $this->assertGreaterThan($probabilityAfterSecondAttempt, $probabilityAfterAcceptedAttempt);
        $this->assertNotNull($acceptedAttempt->fresh()->knowledge_traced_at);
    }

    public function test_attempt_after_accepted_solution_does_not_change_probability_again(): void
    {
        [$user, $task, $category] = $this->userTaskAndCategory();

        $acceptedAttempt = $this->attempt($user, $task, TaskStatus::COMPLETED);
        app(KnowledgeTracingService::class)->updateFromAttempt($acceptedAttempt);

        $probabilityAfterAcceptedAttempt = UserCategoryMastery::query()
            ->where('user_id', $user->id)
            ->where('category_id', $category->id)
            ->value('probability');

        $nextAttempt = $this->attempt($user, $task, TaskStatus::INCORRECT_RESULT);
        app(KnowledgeTracingService::class)->updateFromAttempt($nextAttempt);

        $this->assertSame(
            (float) $probabilityAfterAcceptedAttempt,
            (float) UserCategoryMastery::query()
                ->where('user_id', $user->id)
                ->where('category_id', $category->id)
                ->value('probability')
        );
        $this->assertNotNull($nextAttempt->fresh()->knowledge_traced_at);
    }

    private function userTaskAndCategory(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->firstOrFail();
        $task = Task::query()->create([
            'title' => 'BKT test task',
            'description' => 'Test',
            'rating' => 900,
            'is_public' => true,
        ]);
        $task->categories()->attach($category->id);

        return [$user, $task, $category];
    }

    private function attempt(User $user, Task $task, TaskStatus $status): Attempt
    {
        return Attempt::query()->create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => $status,
            'description' => $status->value,
        ]);
    }
}
