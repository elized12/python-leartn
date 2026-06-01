<?php

namespace App\Providers;

use App\View\Components\NotificationFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Blade::component(NotificationFactory::class, 'notification-factory');
    }

    public function boot(): void
    {
        //
    }
}
