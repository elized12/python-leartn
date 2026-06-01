<?php

use App\Console\Commands\InstallJudgePythonImage;
use App\Console\Commands\InstallJudgePythonPandasImage;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\NotificationChecker;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        InstallJudgePythonImage::class,
        InstallJudgePythonPandasImage::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureUserIsNotBlocked::class,
        ]);

        $middleware->alias([
            'admin' => CheckAdmin::class,
            'notification-checker' => NotificationChecker::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
