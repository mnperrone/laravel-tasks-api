<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskPriorityUuidTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_create_task_with_priority_assigns_uuid_and_priority(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks', [
            'title' => 'Priority Task',
            'priority' => 'high',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Priority Task',
            'user_id' => $user->id,
            'priority' => 'high',
        ]);

        $task = Task::where('title', 'Priority Task')->first();
        $this->assertNotNull($task);

        // The task primary key 'id' should be present and a valid UUID.
        $this->assertNotEmpty($task->id);
        $this->assertTrue(Str::isUuid($task->id), 'Task id must be a valid UUID');
    }
}
