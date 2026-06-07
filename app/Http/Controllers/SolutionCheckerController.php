<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\CheckSolution;
use App\Models\Task\Contest;
use App\Models\Task\ContestParticipant;
use App\Models\Task\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolutionCheckerController extends Controller
{
    public function checkSolution(Request $request, int $taskId): JsonResponse
    {
        $request->validate(
            [
                'code' => 'required|string',
                'contest_id' => 'nullable|integer|exists:contest,id',
            ]
        );

        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'task not found'
                ],
                404
            );
        }

        $contestId = $request->integer('contest_id') ?: null;
        if ($contestId) {
            $contest = Contest::query()->with('tasks')->find($contestId);

            if (
                !$contest
                || !$contest->isRunning()
                || !$contest->tasks->contains('id', $task->id)
                || !ContestParticipant::where('contest_id', $contest->id)->where('user_id', Auth::id())->exists()
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Отправка решения в этот контест недоступна',
                ], 403);
            }
        }

        CheckSolution::dispatch($request->input('code'), $taskId, Auth::user()->id, $contestId);

        return response()->json([
            'status' => true,
            'message' => 'attempt sent'
        ]);
    }
}
