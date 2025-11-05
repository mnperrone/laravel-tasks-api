<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento Task Created
 *
 * Se dispara cuando se crea una nueva tarea en el sistema.
 */
class TaskCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Instancia de la tarea.
     *
     * @var Task
     */
    public Task $task;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param Task $task
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
