<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task API Test
 * 
 * Feature tests for Task API endpoints including authentication
 * and CRUD operations.
 */
class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can login and receive JWT token.
     *
     * @return void
     */
    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user',
            ]);
    }

    /**
     * Test login fails with invalid credentials.
     *
     * @return void
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
            ]);
    }

    /**
     * Test authenticated user can create a task.
     *
     * @return void
     */
    public function test_authenticated_user_can_create_task(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'This is a test task',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'is_completed',
                    'user_id',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test unauthenticated user cannot create task.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_create_task(): void
    {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can list their tasks.
     *
     * @return void
     */
    public function test_user_can_list_their_tasks(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        Task::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can view a specific task.
     *
     * @return void
     */
    public function test_user_can_view_specific_task(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Specific Task',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/tasks/' . $task->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'title' => 'Specific Task',
                ],
            ]);
    }

    /**
     * Test user can update their own task.
     *
     * @return void
     */
    public function test_user_can_update_their_own_task(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/tasks/' . $task->id, [
            'title' => 'Updated Task Title',
            'is_completed' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Updated Task Title',
                    'is_completed' => true,
                ],
            ]);
    }

    /**
     * Test user cannot update another user's task.
     *
     * @return void
     */
    public function test_user_cannot_update_another_users_task(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = auth('api')->login($user1);

        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/tasks/' . $task->id, [
            'title' => 'Updated Task',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test user can delete their own task.
     *
     * @return void
     */
    public function test_user_can_delete_their_own_task(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/tasks/' . $task->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * Test user cannot delete another user's task.
     *
     * @return void
     */
    public function test_user_cannot_delete_another_users_task(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = auth('api')->login($user1);

        $task = Task::factory()->create(['user_id' => $user2->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/tasks/' . $task->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }

    /**
     * Test task creation requires title.
     *
     * @return void
     */
    public function test_task_creation_requires_title(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/tasks', [
            'description' => 'Task without title',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }
}
