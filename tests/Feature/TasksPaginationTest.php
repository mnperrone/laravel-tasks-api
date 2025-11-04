<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_endpoint_paginates_results(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Task::factory()->count(30)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tasks?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta'
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    public function test_tasks_endpoint_filters_by_priority_and_completed(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Create tasks with different priorities and completed states
        Task::factory()->create(['user_id' => $user->id, 'priority' => 'low', 'is_completed' => false]);
        Task::factory()->create(['user_id' => $user->id, 'priority' => 'high', 'is_completed' => true]);
        Task::factory()->create(['user_id' => $user->id, 'priority' => 'medium', 'is_completed' => false]);

        // Filter by priority=high
        $res1 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/tasks?priority=high');
        $res1->assertStatus(200);
        $this->assertCount(1, $res1->json('data'));

        // Filter by completed=true
        $res2 = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->getJson('/api/tasks?completed=true');
        $res2->assertStatus(200);
        $this->assertEquals(true, $res2->json('data.0.is_completed'));
    }
}
