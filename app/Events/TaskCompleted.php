<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Task
     */
    public Task $task;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param Task $task
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}
