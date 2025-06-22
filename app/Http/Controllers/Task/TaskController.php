<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Jobs\CheckSolution;
use App\Models\Task\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function checkSolution(Request $request, int $taskId): JsonResponse
    {
        $request->validate(
            [
                'code' => 'required|string'
            ]
        );

        $task = Task::find($taskId);
        if (!$task) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'task not found'
                ],
                404
            );
        }

        CheckSolution::dispatch($request->input('code'), $taskId, auth()->user()->id);

        return response()->json([
            'status' => 'ok',
            'message' => 'attempt sent'
        ]);
    }

    public function showTaskPage(int $taskId): View
    {
        $task = Task::find($taskId);
        if (!$task) {
            return view('task.task-not-found');
        }

        return view('task.task', ['task' => $task]);
    }
}
