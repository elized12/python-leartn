<?php

use App\Http\Controllers\Admin\Task\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Task\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'showHomePage'])
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::put('/notification/{notificationId}', [NotificationController::class, 'hiddenNotification'])
        ->where('notifacationId', '[0-9]+')->name('notification.hidden');

    Route::get('/task/solution/{taskId}', [TaskController::class, 'showTaskPage'])
        ->name('task.solution');

    Route::post('/task/solution/{taskId}', [TaskController::class, 'checkSolution'])
        ->where('taskId', '[0-9]+')
        ->name('task.attempt.send');

    Route::get('/profile/{userId}', [ProfileController::class, 'showProfilePage'])
        ->where('userId', '[0-9]+')
        ->name('user.profile');

    Route::middleware('admin')->group(function () {

        Route::get('/admin/tasks', [AdminController::class, 'showTasksPage'])
            ->name('admin.tasks.show');

        Route::get('/admin/task', [AdminTaskController::class, 'showCreatePage'])
            ->name('admin.task-create.show');

        Route::post('/admin/task/create', [AdminTaskController::class, 'createTask'])
            ->name('admin.task-create.create');

        Route::get('/admin/panel', [AdminController::class, 'showMainPage'])
            ->name('admin.main.show');
    });
});

require_once 'auth.php';
