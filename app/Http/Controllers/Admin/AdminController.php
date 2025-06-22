<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task\Task;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function showMainPage(): View
    {
        return view('admin.main');
    }

    public function showTasksPage(): View
    {
        $tasks = Task::paginate(10);
        return view('admin.task.tasks', ['tasks' => $tasks]);
    }
}
