<?php

namespace App\Listeners;

use App\Events\TaskCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Task Created Listener
 * 
 * Logs information when a new task is created.
 * Implements ShouldQueue to process asynchronously.
 */
class LogTaskCreated implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
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
