<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener Log Task Completed
 *
 * Genera logs cuando una tarea cambia a estado completado.
 */
class LogTaskCompleted implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    /**
     * Maneja el evento de tarea completada.
     *
     * @param TaskCompleted $event
     * @return void
     */
    public function handle(TaskCompleted $event): void
    {
        $task = $event->task;

        Log::info('Task completed', [
            'task_id' => $task->id,
            'user_id' => $task->user_id,
            'completed_at' => now()->toDateTimeString(),
        ]);
    }
}
