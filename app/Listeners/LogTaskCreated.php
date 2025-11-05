<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener Log Task Created
 *
 * Registra información cuando se crea una nueva tarea.
 * Implementa ShouldQueue para procesar de forma asíncrona.
 */
class LogTaskCreated implements ShouldQueue
{
    /**
     * Crea el listener del evento.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Maneja el evento.
     *
     * @param TaskCreated $event
     * @return void
     */
    public function handle(TaskCreated $event): void
    {
        $task = $event->task;
        
        Log::info('Task created', [
            'task_id' => $task->id,
            'title' => $task->title,
            'user_id' => $task->user_id,
            'created_at' => $task->created_at->toDateTimeString(),
        ]);
    }
}
