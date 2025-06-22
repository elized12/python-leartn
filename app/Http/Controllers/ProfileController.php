<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task\Attempt;
use App\Models\User;
use App\Service\Task\TaskStatus;
use Illuminate\Contracts\View\View;

class ProfileController extends Controller
{
    public function showProfilePage(int $userId): View
    {
        $user = User::find($userId);
        if (!$user) {
            return view('user.not-found-profile');
        }

        $latestAttemptsIds = Attempt::selectRaw('MAX(id) as id')
            ->where('status', TaskStatus::COMPLETED)
            ->where('user_id', $userId)
            ->groupBy('task_id')
            ->pluck('id');

        $tasksCompleted = Attempt::whereIn('id', $latestAttemptsIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $tasksCompletedCount = Attempt::where('status', '=', TaskStatus::COMPLETED)
            ->where('user_id', '=', $userId)
            ->select('task_id')
            ->distinct('task_id')
            ->count();

        return view('user.profile', [
            'user' => $user,
            'tasksCompleted' => $tasksCompleted,
            'tasksCompletedCount' => $tasksCompletedCount
        ]);
    }
}
