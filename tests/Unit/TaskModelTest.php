<?php

namespace Tests\Unit;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_generates_uuid_and_priority_is_valid(): void
    {
        $task = Task::factory()->create();

            // Priority should always be one of the allowed values.
            $this->assertContains($task->priority, ['low','medium','high']);

            // The primary key 'id' must be set and be a valid UUID.
            $this->assertNotEmpty($task->id);
            $this->assertTrue(Str::isUuid($task->id), 'Task id must be a valid UUID');
    }

    public function test_creating_task_with_specific_priority_is_persisted(): void
    {
        $task = Task::factory()->create(['priority' => 'high']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 'high',
        ]);
    }
}
