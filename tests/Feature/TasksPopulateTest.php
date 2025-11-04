<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TasksPopulateTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_populate_inserts_and_updates_tasks_using_external_api()
    {
        // Ensure API key env is set for the controller check
        putenv('API_POPULATE_KEY=supersecretapikey');

        // Create a user and obtain a bearer token
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Prepare fake external todos: one matches existing title, one new
        $externalTodos = [
            [
                'userId' => 1,
                'id' => 101,
                'title' => 'Existing Task',
                'completed' => true,
            ],
            [
                'userId' => 1,
                'id' => 102,
                'title' => 'New External Task',
                'completed' => false,
            ],
        ];

        // Seed one existing task for this user with title 'Existing Task'
        Task::factory()->create([
            'title' => 'Existing Task',
            'user_id' => $user->id,
            'is_completed' => false,
        ]);

        // Fake the external HTTP call
        Http::fake([
            'https://jsonplaceholder.typicode.com/todos' => Http::response($externalTodos, 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-API-KEY' => 'supersecretapikey',
        ])->getJson('/api/tasks/populate');

        $response->assertStatus(200)->assertJsonStructure(['inserted']);

        // 'Existing Task' should not be duplicated, but should be updated to completed=true
        $this->assertDatabaseHas('tasks', [
            'title' => 'Existing Task',
            'user_id' => $user->id,
            'is_completed' => true,
        ]);

        // 'New External Task' should have been inserted
        $this->assertDatabaseHas('tasks', [
            'title' => 'New External Task',
            'user_id' => $user->id,
        ]);
    }

    public function test_tasks_populate_requires_valid_api_key()
    {
        putenv('API_POPULATE_KEY=supersecretapikey');

        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // No HTTP fake needed here — request should be rejected before calling external API
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-API-KEY' => 'wrongkey',
        ])->getJson('/api/tasks/populate');

        $response->assertStatus(403)->assertJson(['message' => 'Invalid API key']);
    }
}
