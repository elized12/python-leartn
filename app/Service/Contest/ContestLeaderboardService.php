<?php

namespace App\Service\Contest;

use App\Models\Task\Contest;
use App\Models\Task\ContestParticipant;
use App\Service\Task\TaskStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContestLeaderboardService
{
    public function leaderboard(int $contestId, ?int $limit = null): Collection
    {
        $query = DB::table('contest_participant as cp')
            ->join('users as u', 'u.id', '=', 'cp.user_id')
            ->leftJoin('attempt_solution as a', function ($join) use ($contestId) {
                $join->on('a.user_id', '=', 'cp.user_id')
                    ->where('a.contest_id', '=', $contestId)
                    ->where('a.status', '=', TaskStatus::COMPLETED->value);
            })
            ->where('cp.contest_id', $contestId)
            ->select(
                'u.id',
                'u.name',
                'cp.joined_at',
                DB::raw('COUNT(DISTINCT a.task_id) as solved'),
                DB::raw('COUNT(a.id) as accepted_attempts'),
                DB::raw('MIN(a.created_at) as first_accepted_at'),
                DB::raw('MAX(a.created_at) as last_accepted_at')
            )
            ->groupBy('u.id', 'u.name', 'cp.joined_at')
            ->orderByDesc('solved')
            ->orderByRaw('CASE WHEN MIN(a.created_at) IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('MIN(a.created_at) asc')
            ->orderBy('cp.joined_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->values()
            ->map(function ($row, int $index) {
                $row->rank = $index + 1;
                $row->solved = (int) $row->solved;
                $row->accepted_attempts = (int) $row->accepted_attempts;

                return $row;
            });
    }

    public function userResult(int $contestId, int $userId): ?object
    {
        return $this->leaderboard($contestId)
            ->first(fn($row) => (int) $row->id === $userId);
    }

    public function userContestResults(int $userId, int $limit = 8): Collection
    {
        return ContestParticipant::query()
            ->with(['contest' => fn($query) => $query->withCount(['tasks', 'participants'])])
            ->where('user_id', $userId)
            ->latest('joined_at')
            ->limit($limit)
            ->get()
            ->map(function (ContestParticipant $participant) use ($userId) {
                $contest = $participant->contest;

                if (!$contest instanceof Contest) {
                    return null;
                }

                $contest->user_contest_result = $contest->isStarted()
                    ? $this->userResult($contest->id, $userId)
                    : null;
                $contest->user_joined_at = $participant->joined_at;

                return $contest;
            })
            ->filter()
            ->values();
    }
}
