<?php

namespace App\Http\Controllers;

use App\Models\Task\Attempt;
use App\Models\Task\Contest;
use App\Models\Task\ContestParticipant;
use App\Models\Task\Task;
use App\Service\Contest\ContestLeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContestController extends Controller
{
    public function index(ContestLeaderboardService $leaderboardService): View
    {
        $finishedContests = Contest::query()
            ->where('is_active', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->withCount(['tasks', 'participants'])
            ->latest('ends_at')
            ->limit(6)
            ->get()
            ->map(function (Contest $contest) use ($leaderboardService) {
                $winner = $leaderboardService->leaderboard($contest->id, 1)->first();
                $contest->winner = $winner && $winner->solved > 0 ? $winner : null;

                return $contest;
            });

        return view('contest.index', [
            'contests' => Contest::query()
                ->where('is_active', true)
                ->where(fn($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->withCount(['tasks', 'participants'])
                ->orderByDesc('starts_at')
                ->paginate(10),
            'finishedContests' => $finishedContests,
        ]);
    }

    public function show(Contest $contest, ContestLeaderboardService $leaderboardService): View
    {
        abort_unless($contest->is_active || Auth::user()?->is_admin, 404);

        $isStarted = $contest->isStarted();
        $isRunning = $contest->isRunning();
        $isParticipant = Auth::check()
            && ContestParticipant::where('contest_id', $contest->id)->where('user_id', Auth::id())->exists();

        $leaderboard = $isStarted
            ? $leaderboardService->leaderboard($contest->id, 20)
            : collect();

        return view('contest.show', [
            'contest' => $contest,
            'tasks' => $isStarted ? $contest->tasks()->where('is_public', true)->get() : collect(),
            'leaderboard' => $leaderboard,
            'isParticipant' => $isParticipant,
            'isStarted' => $isStarted,
            'isRunning' => $isRunning,
            'startsAtIso' => $contest->starts_at?->toIso8601String(),
            'recentAttempts' => $isStarted
                ? Attempt::query()
                    ->with(['user', 'task'])
                    ->where('contest_id', $contest->id)
                    ->latest()
                    ->limit(20)
                    ->get()
                : collect(),
            'onlineAttempts' => $isStarted
                ? Attempt::query()
                    ->where('contest_id', $contest->id)
                    ->where('created_at', '>=', now()->subMinutes(15))
                    ->count()
                : 0,
            'durationHours' => $contest->starts_at && $contest->ends_at
                ? max(1, (int) ceil($contest->starts_at->diffInHours($contest->ends_at, false)))
                : 0,
        ]);
    }

    public function join(Contest $contest): RedirectResponse
    {
        if (!$contest->is_active || $contest->isFinished()) {
            return redirect()->route('contests.show', $contest)->with('error', 'К этому контесту уже нельзя присоединиться');
        }

        ContestParticipant::firstOrCreate([
            'contest_id' => $contest->id,
            'user_id' => Auth::id(),
        ], [
            'joined_at' => now(),
        ]);

        return redirect()->route('contests.show', $contest)->with('success', 'Вы присоединились к контесту');
    }

    public function task(Contest $contest, Task $task): RedirectResponse
    {
        if (!$contest->isRunning()) {
            return redirect()->route('contests.show', $contest)->with('error', 'Контест сейчас не идёт');
        }

        if (!$contest->tasks()->where('task.id', $task->id)->exists()) {
            abort(404);
        }

        if (!ContestParticipant::where('contest_id', $contest->id)->where('user_id', Auth::id())->exists()) {
            return redirect()->route('contests.show', $contest)->with('error', 'Сначала присоединитесь к контесту');
        }

        return redirect()->route('task.solution', [
            'taskId' => $task->id,
            'contest_id' => $contest->id,
        ]);
    }
}
