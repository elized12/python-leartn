<?php

use App\Http\Controllers\Admin\ContestController as AdminContestController;
use App\Http\Controllers\Admin\Task\TaskController as AdminTaskController;
use App\Http\Controllers\Admin\Task\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\Task\EnvironmentController as AdminEnvironmentController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\KnowledgeTracingSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ContestController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SolutionCheckerController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskController as MainTasksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'showHomePage'])
    ->name('home');

Route::get('/dashboard', [HomeController::class, 'showHomePage'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/notification/{notificationId}', [NotificationController::class, 'hiddenNotification'])
        ->where('notifacationId', '[0-9]+')->name('notification.hidden');

    Route::post('/contest/{contest}/join', [ContestController::class, 'join'])
        ->where('contest', '[0-9]+')
        ->name('contests.join');

    Route::get('/contest/{contest}/task/{task}', [ContestController::class, 'task'])
        ->where(['contest' => '[0-9]+', 'task' => '[0-9]+'])
        ->name('contests.task');

    Route::get('/task/solution/{taskId}', [TaskController::class, 'showTaskPage'])
        ->name('task.solution');

    Route::get('/task/solution/{taskId}/author-solution', [TaskController::class, 'showAuthorSolution'])
        ->where('taskId', '[0-9]+')
        ->name('task.author-solution.show');

    Route::post('/task/solution/{taskId}/ai-hint', [TaskController::class, 'aiHint'])
        ->where('taskId', '[0-9]+')
        ->name('task.ai-hint');

    Route::get('/task/solution/{taskId}/files/{fileId}', [TaskController::class, 'downloadPublicFile'])
        ->where(['taskId' => '[0-9]+', 'fileId' => '[0-9]+'])
        ->name('task.files.download');

    Route::post('/task/solution/{taskId}', [SolutionCheckerController::class, 'checkSolution'])
        ->where('taskId', '[0-9]+')
        ->name('task.attempt.send');

    Route::post('/task/solution/{taskId}/comments', [TaskController::class, 'storeComment'])
        ->where('taskId', '[0-9]+')
        ->name('task.comments.store');

    Route::post('/courses/assets', [CourseController::class, 'uploadAsset'])
        ->name('courses.assets.upload');

    Route::post('/courses/join', [CourseController::class, 'joinCourse'])
        ->name('course.join');

    Route::get('/course/lesson/{lessonId}', [CourseController::class, 'getLesson'])
        ->where('lessonId', '[0-9]+')
        ->name('course.lesson.get');

    Route::post('/course/lesson/{lesson}/complete', [CourseController::class, 'completeLesson'])
        ->where('lesson', '[0-9]+')
        ->name('course.lesson.complete');

    Route::middleware('notification-checker')->group(function () {
        Route::get('/courses', [CourseController::class, 'showCoursesPage'])
            ->name('courses.show');

        Route::get('/tasks', [MainTasksController::class, 'showTasksPage'])
            ->name('tasks.show');

        Route::get('/rating', [RatingController::class, 'index'])
            ->name('rating.index');

        Route::get('/profile/{userId}', [ProfileController::class, 'showProfilePage'])
            ->where('userId', '[0-9]+')
            ->name('user.profile');

        Route::get('/preview/course/{courseName}', [CourseController::class, 'showPreviewCourse'])
            ->where('courseName', '[-a-zA-Z0-9]+')
            ->name('preview.course.show');

        Route::get('/course/{courseName}', [CourseController::class, 'showCoursePage'])
            ->where('courseName', '[-a-zA-Z0-9]+')
            ->name('course.show');

        Route::get('/contests', [ContestController::class, 'index'])
            ->name('contests.index');

        Route::get('/contest/{contest}', [ContestController::class, 'show'])
            ->name('contests.show');
    });

    Route::middleware('admin')->group(function () {
        Route::middleware('notification-checker')->group(function () {
            Route::get('/courses/create', [CourseController::class, 'showCreatePage'])
                ->name('courses.create.show');


            Route::get('/courses/drafts', [CourseController::class, 'showDraftsPage'])
                ->name('courses.drafts.show');

            Route::get('/courses/{course}/edit', [CourseController::class, 'showEditPage'])
                ->where('course', '[0-9]+')
                ->name('courses.edit.show');
        });

        Route::put('/courses/{course}', [CourseController::class, 'updateCourse'])
            ->where('course', '[0-9]+')
            ->name('courses.update');

        Route::delete('/courses/{course}', [CourseController::class, 'destroyCourse'])
            ->where('course', '[0-9]+')
            ->name('courses.destroy');

        Route::post('/courses/create', [CourseController::class, 'createCourse'])
            ->name('courses.create.create');

        Route::get('/admin/contests', [AdminContestController::class, 'index'])
            ->name('admin.contests.index');

        Route::post('/admin/contests', [AdminContestController::class, 'store'])
            ->name('admin.contests.store');

        Route::put('/admin/contests/{contest}', [AdminContestController::class, 'update'])
            ->where('contest', '[0-9]+')
            ->name('admin.contests.update');

        Route::post('/admin/contests/{contest}/start-now', [AdminContestController::class, 'startNow'])
            ->where('contest', '[0-9]+')
            ->name('admin.contests.start-now');

        Route::delete('/admin/contests/{contest}', [AdminContestController::class, 'destroy'])
            ->where('contest', '[0-9]+')
            ->name('admin.contests.destroy');

        Route::get('/admin/tasks', [AdminController::class, 'showTasksPage'])
            ->name('admin.tasks.show');

        Route::get('/admin/task', [AdminTaskController::class, 'showCreatePage'])
            ->name('admin.task-create.show');

        Route::post('/admin/task/create', [AdminTaskController::class, 'createTask'])
            ->name('admin.task-create.create');

        Route::get('/admin/task/{task}/edit', [AdminTaskController::class, 'showEditPage'])
            ->where('task', '[0-9]+')
            ->name('admin.task.edit');

        Route::put('/admin/task/{task}', [AdminTaskController::class, 'updateTask'])
            ->where('task', '[0-9]+')
            ->name('admin.task.update');

        Route::get('/admin/environments', [AdminEnvironmentController::class, 'index'])
            ->name('admin.environments.index');

        Route::post('/admin/environments', [AdminEnvironmentController::class, 'store'])
            ->name('admin.environments.store');

        Route::post('/admin/environments/install-default', [AdminEnvironmentController::class, 'installDefault'])
            ->name('admin.environments.install-default');

        Route::post('/admin/environments/install-pandas', [AdminEnvironmentController::class, 'installPandas'])
            ->name('admin.environments.install-pandas');

        Route::put('/admin/environments/{environment}', [AdminEnvironmentController::class, 'update'])
            ->name('admin.environments.update');

        Route::delete('/admin/environments/{environment}', [AdminEnvironmentController::class, 'destroy'])
            ->name('admin.environments.destroy');

        Route::get('/admin/categories', [AdminCategoryController::class, 'index'])
            ->name('admin.categories.index');

        Route::post('/admin/categories', [AdminCategoryController::class, 'store'])
            ->name('admin.categories.store');

        Route::put('/admin/categories/{category}', [AdminCategoryController::class, 'update'])
            ->name('admin.categories.update');

        Route::delete('/admin/categories/{category}', [AdminCategoryController::class, 'destroy'])
            ->name('admin.categories.destroy');

        Route::get('/admin/users', [AdminUserController::class, 'index'])
            ->name('admin.users.index');

        Route::get('/admin/ai-settings', [AiSettingsController::class, 'index'])
            ->name('admin.ai-settings.index');

        Route::put('/admin/ai-settings', [AiSettingsController::class, 'update'])
            ->name('admin.ai-settings.update');

        Route::put('/admin/ai-settings/prompt', [AiSettingsController::class, 'updatePrompt'])
            ->name('admin.ai-settings.prompt.update');

        Route::post('/admin/ai-settings/install', [AiSettingsController::class, 'install'])
            ->name('admin.ai-settings.install');

        Route::get('/admin/knowledge-tracing', [KnowledgeTracingSettingsController::class, 'index'])
            ->name('admin.knowledge-tracing.index');

        Route::put('/admin/knowledge-tracing', [KnowledgeTracingSettingsController::class, 'update'])
            ->name('admin.knowledge-tracing.update');

        Route::put('/admin/users/{user}/toggle-block', [AdminUserController::class, 'toggleBlock'])
            ->where('user', '[0-9]+')
            ->name('admin.users.toggle-block');

        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])
            ->where('user', '[0-9]+')
            ->name('admin.users.destroy');

        Route::get('/admin/panel', [AdminController::class, 'showMainPage'])
            ->name('admin.main.show');
    });
});

require __DIR__ . '/auth.php';
