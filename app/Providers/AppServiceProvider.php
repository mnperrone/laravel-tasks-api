<?php

namespace App\Providers;

use App\Events\TaskCreated;
use App\Events\TaskCompleted;
use App\Listeners\LogTaskCreated;
use App\Listeners\LogTaskCompleted;
use App\Models\Task;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(
            TaskCreated::class,
            LogTaskCreated::class,
        );

        Event::listen(
            TaskCompleted::class,
            LogTaskCompleted::class,
        );

        // Register policies
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
