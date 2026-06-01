<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\CheckSolution;
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
                'code' => 'required|string'
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

        CheckSolution::dispatch($request->input('code'), $taskId, Auth::user()->id);

        return response()->json([
            'status' => true,
            'message' => 'attempt sent'
        ]);
    }
}
