<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Task Policy
 *
 * Defines authorization logic for Task operations.
 * Owners can update/delete their tasks, others can only view.
 */
class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the task.
     * Only the owner or admin can view individual tasks.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create tasks.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the task.
     * Only the owner or admin can update the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        // Allow the owner or an admin to update the task
        return $user->id === $task->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the task.
     * Only the owner or admin can delete the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function restore(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    /**
     * Determine whether the user can permanently delete the task.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
