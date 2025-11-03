<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogTaskCompleted implements ShouldQueue
{
    public function __construct()
    {
        //
    }

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
