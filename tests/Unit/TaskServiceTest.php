<?php

namespace Tests\Unit;

use App\Events\TaskCreated;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Task Service Test
 * 
 * Unit tests for TaskService business logic.
 */
class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaskService $taskService;
    protected TaskRepository $taskRepository;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->taskRepository = new TaskRepository();
        $this->taskService = new TaskService($this->taskRepository);
    }

    /**
     * Test task creation fires TaskCreated event.
     *
     * @return void
     */
    public function test_creating_task_fires_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        
        $task = $this->taskService->createTask([
            'title' => 'Test Task',
            'description' => 'Test Description',
        ], $user);

        Event::assertDispatched(TaskCreated::class, function ($event) use ($task) {
            return $event->task->id === $task->id;
        });
    }

    /**
     * Test user can get all their tasks.
     *
     * @return void
     */
    public function test_user_can_get_all_their_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);
        Task::factory()->count(2)->create(); // Other user's tasks

        $tasks = $this->taskService->getAllForUser($user);

        $this->assertCount(3, $tasks);
    }

    /**
     * Test task can be marked as completed.
     *
     * @return void
     */
    public function test_task_can_be_marked_as_completed(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'is_completed' => false,
        ]);

        $updatedTask = $this->taskService->markAsCompleted($task, $user);

        $this->assertTrue($updatedTask->is_completed);
    }

    /**
     * Test task can be marked as incomplete.
     *
     * @return void
     */
    public function test_task_can_be_marked_as_incomplete(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'is_completed' => true,
        ]);

        $updatedTask = $this->taskService->markAsIncomplete($task, $user);

        $this->assertFalse($updatedTask->is_completed);
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
        
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You do not have permission to update this task.');

        $this->taskService->updateTask($task, ['title' => 'New Title'], $user1);
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
        
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('You do not have permission to delete this task.');

        $this->taskService->deleteTask($task, $user1);
    }

    /**
     * Test creating task assigns user_id correctly.
     *
     * @return void
     */
    public function test_creating_task_assigns_user_id_correctly(): void
    {
        $user = User::factory()->create();
        
        $task = $this->taskService->createTask([
            'title' => 'Test Task',
        ], $user);

        $this->assertEquals($user->id, $task->user_id);
    }

    /**
     * Test updating task updates fields correctly.
     *
     * @return void
     */
    public function test_updating_task_updates_fields_correctly(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $updatedTask = $this->taskService->updateTask($task, [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ], $user);

        $this->assertEquals('Updated Title', $updatedTask->title);
        $this->assertEquals('Updated Description', $updatedTask->description);
    }
}
