<?php

namespace App\Service;

use App\Models\Task\Attempt;
use App\Models\Task\Category;
use App\Models\Task\Task;
use App\Models\User;
use App\Models\UserCategoryMastery;
use App\Service\Task\TaskStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KnowledgeTracingService
{
    public function __construct(
        private readonly KnowledgeTracingSettingsService $settingsService
    ) {}

    public function updateFromAttempt(Attempt $attempt): void
    {
        DB::transaction(function () use ($attempt) {
            /** @var Attempt $attempt */
            $attempt = Attempt::query()->lockForUpdate()->find($attempt->id);

            if (!$attempt || $attempt->knowledge_traced_at !== null) {
                return;
            }

            if ($this->hasAlreadyTracedTask($attempt)) {
                $attempt->forceFill(['knowledge_traced_at' => now()])->save();
                return;
            }

            $attempt->loadMissing(['task.categories', 'user']);
            $task = $attempt->task;

            if (!$task || !$attempt->user || $task->categories->isEmpty()) {
                $attempt->forceFill(['knowledge_traced_at' => now()])->save();
                return;
            }

            $settings = $this->settingsService->settings();
            $isCorrect = $attempt->status === TaskStatus::COMPLETED;

            foreach ($task->categories as $category) {
                $mastery = UserCategoryMastery::query()->firstOrCreate([
                    'user_id' => $attempt->user_id,
                    'category_id' => $category->id,
                ], [
                    'probability' => $settings['prior'],
                ]);

                $mastery->probability = $this->calculateProbability(
                    (float) $mastery->probability,
                    $isCorrect,
                    $settings
                );
                $mastery->save();
            }

            $attempt->forceFill(['knowledge_traced_at' => now()])->save();
        });
    }

    public function getUserKnowledgeProfile(User $user): Collection
    {
        return UserCategoryMastery::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->orderBy('probability')
            ->get()
            ->map(fn(UserCategoryMastery $mastery) => $this->profileRow($mastery));
    }

    public function getRecommendedTasks(User $user, int $limit = 5): Collection
    {
        $solvedTaskIds = Attempt::query()
            ->where('user_id', $user->id)
            ->where('status', TaskStatus::COMPLETED->value)
            ->pluck('task_id')
            ->unique()
            ->values();

        $weakMasteries = UserCategoryMastery::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->orderBy('probability')
            ->limit(3)
            ->get();

        if ($weakMasteries->isEmpty()) {
            return $this->starterTasks($solvedTaskIds, $limit);
        }

        $priority = $weakMasteries
            ->values()
            ->mapWithKeys(fn(UserCategoryMastery $mastery, int $index) => [$mastery->category_id => $index]);

        return Task::query()
            ->where('is_public', true)
            ->with('categories')
            ->whereNotIn('id', $solvedTaskIds)
            ->whereHas('categories', fn($query) => $query->whereIn('task_category.id', $priority->keys()))
            ->orderBy('rating')
            ->orderBy('id')
            ->limit($limit * 4)
            ->get()
            ->map(function (Task $task) use ($priority, $weakMasteries) {
                $category = $task->categories
                    ->sortBy(fn(Category $category) => $priority[$category->id] ?? 999)
                    ->first();
                $mastery = $weakMasteries->firstWhere('category_id', $category?->id);
                $percentage = $mastery ? (int) round($mastery->probability * 100) : 30;

                $task->recommendation_category = $category;
                $task->recommendation_percentage = $percentage;
                $task->recommendation_reason = $category
                    ? "Рекомендуется, потому что категория «{$category->name}» освоена на {$percentage}%."
                    : 'Рекомендуется как следующая нерешённая задача.';

                return $task;
            })
            ->sortBy(fn(Task $task) => sprintf(
                '%03d-%08d-%08d',
                $priority[$task->recommendation_category?->id] ?? 999,
                $task->rating ?? 0,
                $task->id
            ))
            ->take($limit)
            ->values();
    }

    public function level(float $probability): string
    {
        return match (true) {
            $probability < 0.40 => 'weak',
            $probability < 0.70 => 'need_practice',
            $probability < 0.85 => 'almost_mastered',
            default => 'mastered',
        };
    }

    public function levelLabel(string $level): string
    {
        return match ($level) {
            'weak' => 'слабая тема',
            'need_practice' => 'требуется повторение',
            'almost_mastered' => 'почти освоено',
            'mastered' => 'освоено',
            default => 'неизвестно',
        };
    }

    public function calculateProbability(float $probability, bool $isCorrect, array $settings): float
    {
        // Упрощенная Bayesian Knowledge Tracing: категория задачи считается учебным навыком.
        $p = max(0, min(1, $probability));
        $learn = (float) $settings['learn'];
        $guess = (float) $settings['guess'];
        $slip = (float) $settings['slip'];

        $observed = $isCorrect
            ? $this->observedCorrect($p, $guess, $slip)
            : $this->observedIncorrect($p, $guess, $slip);

        return round(max(0, min(1, $observed + (1 - $observed) * $learn)), 4);
    }

    private function hasAlreadyTracedTask(Attempt $attempt): bool
    {
        return Attempt::query()
            ->where('user_id', $attempt->user_id)
            ->where('task_id', $attempt->task_id)
            ->whereKeyNot($attempt->id)
            ->whereNotNull('knowledge_traced_at')
            ->exists();
    }

    private function observedCorrect(float $p, float $guess, float $slip): float
    {
        $denominator = ($p * (1 - $slip)) + ((1 - $p) * $guess);
        return $denominator <= 0 ? $p : ($p * (1 - $slip)) / $denominator;
    }

    private function observedIncorrect(float $p, float $guess, float $slip): float
    {
        $denominator = ($p * $slip) + ((1 - $p) * (1 - $guess));
        return $denominator <= 0 ? $p : ($p * $slip) / $denominator;
    }

    private function profileRow(UserCategoryMastery $mastery): object
    {
        $probability = (float) $mastery->probability;
        $level = $this->level($probability);

        return (object) [
            'category_id' => $mastery->category_id,
            'category_name' => $mastery->category?->name ?? 'Категория удалена',
            'probability' => $probability,
            'percentage' => (int) round($probability * 100),
            'level' => $level,
            'level_label' => $this->levelLabel($level),
        ];
    }

    private function starterTasks(Collection $solvedTaskIds, int $limit): Collection
    {
        return Task::query()
            ->where('is_public', true)
            ->with('categories')
            ->whereNotIn('id', $solvedTaskIds)
            ->orderBy('rating')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Task $task) {
                $category = $task->categories->first();
                $task->recommendation_category = $category;
                $task->recommendation_percentage = null;
                $task->recommendation_reason = $category
                    ? "Стартовая рекомендация по категории «{$category->name}»."
                    : 'Стартовая рекомендация для первого решения.';
            });
    }
}
