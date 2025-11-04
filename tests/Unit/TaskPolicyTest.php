<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * Task Policy Test
 *
 * Unit tests for TaskPolicy authorization logic.
 */
class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TaskPolicy $policy;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TaskPolicy();
    }

    /**
     * Test view permissions: only owner or admin can view a specific task.
     *
     * @return void
     */
    public function test_only_owner_or_admin_can_view_task(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create();

        // ensure admin role exists and assign to admin user
        Role::create(['name' => 'admin']);
        $admin->assignRole('admin');

        $task = Task::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($this->policy->view($owner, $task));
        $this->assertFalse($this->policy->view($other, $task));
        $this->assertTrue($this->policy->view($admin, $task));
    }

    /**
     * Test any user can view any tasks list.
     *
     * @return void
     */
    public function test_any_user_can_view_any_tasks(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /**
     * Test any authenticated user can create tasks.
     *
     * @return void
     */
    public function test_authenticated_user_can_create_tasks(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /**
     * Test owner can update their task.
     *
     * @return void
     */
    public function test_owner_can_update_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $task));
    }

    /**
     * Test non-owner cannot update another user's task.
     *
     * @return void
     */
    public function test_non_owner_cannot_update_another_users_task(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $this->assertFalse($this->policy->update($user1, $task));
    }

    /**
     * Test owner can delete their task.
     *
     * @return void
     */
    public function test_owner_can_delete_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $task));
    }

    /**
     * Test non-owner cannot delete another user's task.
     *
     * @return void
     */
    public function test_non_owner_cannot_delete_another_users_task(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user2->id]);

        $this->assertFalse($this->policy->delete($user1, $task));
    }

    /**
     * Test owner can restore their task.
     *
     * @return void
     */
    public function test_owner_can_restore_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->restore($user, $task));
    }

    /**
     * Test owner can force delete their task.
     *
     * @return void
     */
    public function test_owner_can_force_delete_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->forceDelete($user, $task));
    }
}
