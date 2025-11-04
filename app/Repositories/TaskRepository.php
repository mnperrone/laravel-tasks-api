<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Task Repository
 * 
 * Handles all database operations for Task model.
 * Provides abstraction layer for data access.
 */
class TaskRepository
{
    /**
     * Get all tasks with optional filtering.
     *
     * @param int|null $userId Filter by user ID
     * @param bool|null $isCompleted Filter by completion status
     * @return Collection
     */
    public function getAll(?int $userId = null, ?bool $isCompleted = null): Collection
    {
        $query = Task::with('user');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($isCompleted !== null) {
            $query->where('is_completed', $isCompleted);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get paginated tasks.
     *
     * @param int $perPage Number of items per page
     * @param int|null $userId Filter by user ID
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, ?int $userId = null, array $filters = []): LengthAwarePaginator
    {
        $query = Task::with('user');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        // Optional filters
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['completed'])) {
            // Accept 'true'/'false' strings, 1/0, or boolean
            $completed = $filters['completed'];
            if (is_string($completed)) {
                $completed = in_array(strtolower($completed), ['1', 'true', 'yes'], true);
            }
            $query->where('is_completed', (bool) $completed);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find a task by ID.
     *
     * @param int $id
     * @return Task|null
     */
    public function find(int $id): ?Task
    {
        return Task::with('user')->find($id);
    }

    /**
     * Find a task by ID or fail.
     *
     * @param int $id
     * @return Task
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Task
    {
        return Task::with('user')->findOrFail($id);
    }

    /**
     * Create a new task.
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Update a task.
     *
     * @param Task $task
     * @param array $data
     * @return bool
     */
    public function update(Task $task, array $data): bool
    {
        return $task->update($data);
    }

    /**
     * Delete a task.
     *
     * @param Task $task
     * @return bool|null
     * @throws \Exception
     */
    public function delete(Task $task): ?bool
    {
        return $task->delete();
    }

    /**
     * Mark a task as completed.
     *
     * @param Task $task
     * @return bool
     */
    public function markAsCompleted(Task $task): bool
    {
        return $task->update(['is_completed' => true]);
    }

    /**
     * Mark a task as incomplete.
     *
     * @param Task $task
     * @return bool
     */
    public function markAsIncomplete(Task $task): bool
    {
        return $task->update(['is_completed' => false]);
    }

    /**
     * Get tasks by user ID.
     *
     * @param int $userId
     * @return Collection
     */
    public function getByUserId(int $userId): Collection
    {
        return Task::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
