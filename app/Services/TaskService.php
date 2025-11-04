<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * Task Service
 *
 * Contains business logic for task operations.
 * Validates ownership and handles task-related events.
 */
class TaskService
{
    /**
     * Task Repository instance.
     *
     * @var TaskRepository
     */
    protected TaskRepository $repository;

    /**
     * Constructor.
     *
     * @param TaskRepository $repository
     */
    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all tasks for a user.
     * Uses cache (Redis) with 10 minutes TTL.
     */
    public function getAllForUser(User $user, ?bool $isCompleted = null): Collection
    {
        $cacheKey = "tasks:user:{$user->id}" . ($isCompleted !== null ? ":completed:{$isCompleted}" : '');

        return Cache::remember($cacheKey, 600, function () use ($user, $isCompleted) {
            return $this->repository->getAll($user->id, $isCompleted);
        });
    }

    /**
     * Get paginated tasks for a user.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedForUser(User $user, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $user->id, $filters);
    }

    /**
     * Find a task by ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function findById(int $id): ?Task
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @param User $user
     * @return Task
     */
    public function createTask(array $data, User $user): Task
    {
        $data['user_id'] = $user->id;

        $task = $this->repository->create($data);

        // Fire TaskCreated event
        event(new TaskCreated($task));

        // Invalidate cache for this user
        Cache::forget("tasks:user:{$user->id}");

        return $task;
    }

    /**
     * Update a task.
     * Validates that the user owns the task.
     *
     * @param Task $task
     * @param array $data
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTask(Task $task, array $data, User $user): Task
    {
        // Check ownership
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->update($task, $data);

        // Invalidate cache for owner
        Cache::forget("tasks:user:{$task->user_id}");

        return $task->fresh();
    }

    /**
     * Delete a task.
     * Validates that the user owns the task.
     *
     * @param Task $task
     * @param User $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteTask(Task $task, User $user): bool
    {
        // Check ownership
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to delete this task.');
        }

        $result = $this->repository->delete($task);

        if ($result) {
            Cache::forget("tasks:user:{$task->user_id}");
        }

        return $result;
    }

    /**
     * Mark a task as completed.
     *
     * @param Task $task
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsCompleted(Task $task, User $user): Task
    {
        // Check ownership
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->markAsCompleted($task);

        // Fire TaskCompleted event
        event(new TaskCompleted($task->fresh()));

        // Invalidate cache for owner
        Cache::forget("tasks:user:{$task->user_id}");

        return $task->fresh();
    }

    /**
     * Mark a task as incomplete.
     *
     * @param Task $task
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsIncomplete(Task $task, User $user): Task
    {
        // Check ownership
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->markAsIncomplete($task);

        // Invalidate cache for owner
        Cache::forget("tasks:user:{$task->user_id}");

        return $task->fresh();
    }

    /**
     * Check if a user owns a task.
     *
     * @param Task $task
     * @param User $user
     * @return bool
     */
    protected function userOwnsTask(Task $task, User $user): bool
    {
        return $task->user_id === $user->id;
    }
}
