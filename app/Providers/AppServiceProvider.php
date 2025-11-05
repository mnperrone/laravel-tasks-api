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
     * Registra los servicios de la aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializa los servicios de la aplicación.
     */
    public function boot(): void
    {
    // Registra los listeners de eventos
        Event::listen(
            TaskCreated::class,
            LogTaskCreated::class,
        );

        Event::listen(
            TaskCompleted::class,
            LogTaskCompleted::class,
        );

    // Registra las policies
        Gate::policy(Task::class, TaskPolicy::class);

    }
}
