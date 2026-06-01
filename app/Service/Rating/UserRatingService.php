<?php

namespace App\Service\Rating;

use App\Models\Task\Attempt;
use App\Service\Task\TaskStatus;
use Illuminate\Support\Collection;

class UserRatingService
{
    public function leaderboard(): Collection
    {
        $acceptedAttemptIds = Attempt::query()
            ->where('status', TaskStatus::COMPLETED->value)
            ->selectRaw('MIN(id) as accepted_attempt_id')
            ->groupBy('user_id', 'task_id')
            ->pluck('accepted_attempt_id');

        if ($acceptedAttemptIds->isEmpty()) {
            return collect();
        }

        $acceptedAttempts = Attempt::query()
            ->with(['task', 'user'])
            ->whereIn('id', $acceptedAttemptIds)
            ->get();

        $attemptsByTaskAndUser = Attempt::query()
            ->select(['id', 'status', 'task_id', 'user_id'])
            ->whereIn('user_id', $acceptedAttempts->pluck('user_id')->unique())
            ->whereIn('task_id', $acceptedAttempts->pluck('task_id')->unique())
            ->get()
            ->groupBy(fn(Attempt $attempt) => $this->attemptGroupKey($attempt->user_id, $attempt->task_id));

        $ratings = [];

        foreach ($acceptedAttempts as $attempt) {
            if (!$attempt->user || !$attempt->task) {
                continue;
            }

            $wrongAttempts = $attemptsByTaskAndUser
                ->get($this->attemptGroupKey($attempt->user_id, $attempt->task_id), collect())
                ->filter(fn(Attempt $candidate) => $candidate->id < $attempt->id && $candidate->status !== TaskStatus::COMPLETED)
                ->count();

            $points = $this->pointsForTask((int) $attempt->task->rating, $wrongAttempts);

            $ratings[$attempt->user_id] ??= [
                'user' => $attempt->user,
                'score' => 0,
                'solved_tasks' => 0,
                'attempts_before_accept' => 0,
                'first_try_solutions' => 0,
                'easy_solved' => 0,
                'medium_solved' => 0,
                'hard_solved' => 0,
                'last_solved_at' => null,
            ];

            $ratings[$attempt->user_id]['score'] += $points;
            $ratings[$attempt->user_id]['solved_tasks']++;
            $ratings[$attempt->user_id]['attempts_before_accept'] += $wrongAttempts;
            $ratings[$attempt->user_id]['first_try_solutions'] += $wrongAttempts === 0 ? 1 : 0;
            $ratings[$attempt->user_id][$this->difficultyBucket((int) $attempt->task->rating) . '_solved']++;

            if (
                !$ratings[$attempt->user_id]['last_solved_at']
                || $attempt->created_at->gt($ratings[$attempt->user_id]['last_solved_at'])
            ) {
                $ratings[$attempt->user_id]['last_solved_at'] = $attempt->created_at;
            }
        }

        return collect($ratings)
            ->sort(function (array $left, array $right) {
                if ($left['score'] !== $right['score']) {
                    return $right['score'] <=> $left['score'];
                }

                if ($left['solved_tasks'] !== $right['solved_tasks']) {
                    return $right['solved_tasks'] <=> $left['solved_tasks'];
                }

                return $left['attempts_before_accept'] <=> $right['attempts_before_accept'];
            })
            ->values()
            ->map(function (array $rating, int $index) {
                $rating['rank'] = $index + 1;
                $rating['average_attempts'] = $rating['solved_tasks'] > 0
                    ? round(($rating['attempts_before_accept'] + $rating['solved_tasks']) / $rating['solved_tasks'], 2)
                    : 0;

                return (object) $rating;
            });
    }

    private function pointsForTask(int $rating, int $wrongAttempts): int
    {
        $basePoints = max(50, (int) round($rating / 10));
        $attemptMultiplier = max(0.45, 1 - ($wrongAttempts * 0.08));

        return (int) round($basePoints * $attemptMultiplier);
    }

    private function difficultyBucket(int $rating): string
    {
        if ($rating < 1200) {
            return 'easy';
        }

        if ($rating < 1800) {
            return 'medium';
        }

        return 'hard';
    }

    private function attemptGroupKey(int $userId, int $taskId): string
    {
        return $userId . ':' . $taskId;
    }
}
