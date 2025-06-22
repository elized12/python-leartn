<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Task\Task;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function showHomePage(): View
    {
        if (auth()->check()) {
            $user = auth()->user();
            $notifications = Notification::where('receiver_id', $user->id)
                ->where('visible', true)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $tasks = Task::paginate(10);

        return view('home', [
            'tasks' => $tasks,
            'user' => $user ?? null,
            'notifications' => $notifications ?? null
        ]);
    }
}
