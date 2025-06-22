<?php

use App\Http\Controllers\Admin\Task\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Task\TaskController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', [HomeController::class, 'showHomePage'])
    ->name('home');

Route::get('/task/solution/{taskId}', [TaskController::class, 'showTaskPage'])
    ->name('task.solution');

Route::get('/profile/{userId}', [ProfileController::class, 'showProfilePage'])
    ->where('userId', '[0-9]+')
    ->name('user.profile');

Route::post('/task/solution/{taskId}', [TaskController::class, 'checkSolution'])
    ->where('taskId', '[0-9]+')
    ->name('task.attempt.send');

Route::get('/login', function () {
    Auth::login(User::find(1));
})->where('taskId', '[0-9]+')
    ->name('auth.login');

Route::get('/admin/task/create', function () {
    return view('admin.task.task-create');
})->name('admin.task-create.show');

Route::post('/admin/task/create', [AdminTaskController::class, 'createTask'])
    ->name('admin.task-create.create');

Route::get('/admin/panel', [AdminController::class, 'showMainPage'])
    ->name('admin.main.show');

Route::get('/admin/task', [AdminController::class, 'showTasksPage'])
    ->name('admin.tasks.show');

Route::put('/notification/{notificationId}', [NotificationController::class, 'hiddenNotification'])
    ->where('notifacationId', '[0-9]+')->name('notification.hidden');
