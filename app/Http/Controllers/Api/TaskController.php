<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTaskRequest;
use App\Http\Requests\Api\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Task Controller
 *
 * Handles HTTP requests for task management.
 * Uses TaskService for business logic and TaskResource for responses.
 */
class TaskController extends Controller
{
    /**
     * Task service instance.
     *
     * @var TaskService
     */
    protected TaskService $taskService;

    /**
     * Create a new controller instance.
     *
     * @param TaskService $taskService
     * @return void
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = auth('api')->user();
        // Pagination and optional filters
        $perPage = (int) $request->query('per_page', 15);
        $filters = [];

        if ($request->has('priority')) {
            $filters['priority'] = $request->query('priority');
        }

        if ($request->has('completed')) {
            $filters['completed'] = $request->query('completed');
        }

        $tasks = $this->taskService->getPaginatedForUser($user, $perPage, $filters);

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTaskRequest $request
     * @return JsonResponse
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $task = $this->taskService->createTask($request->validated(), $user);

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->updateTask($task, $request->validated(), $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Task $task
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $user = auth('api')->user();
        $this->taskService->deleteTask($task, $user);

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }

    /**
     * Mark a task as completed.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function complete(Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->markAsCompleted($task, $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Mark a task as incomplete.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function incomplete(Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->markAsIncomplete($task, $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Populate tasks from external API (jsonplaceholder).
     * Requires X-API-KEY header to match API_POPULATE_KEY in .env
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function populate(Request $request): JsonResponse
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey || $apiKey !== env('API_POPULATE_KEY')) {
            return response()->json(['message' => 'Invalid API key'], 403);
        }

        try {
            $response = Http::retry(3, 100)->get('https://jsonplaceholder.typicode.com/todos');

            if (!$response->ok()) {
                Log::error('Failed fetching external tasks', ['status' => $response->status()]);
                return response()->json(['message' => 'Failed to fetch external tasks'], 502);
            }

            $todos = $response->json();
            $inserted = 0;

            foreach ($todos as $todo) {
                // Map external todo to local fields
                $title = Str::limit($todo['title'] ?? 'Untitled', 255);
                $data = [
                    'title' => $title,
                    'description' => null,
                    'is_completed' => !empty($todo['completed']),
                ];

                // Use upsert-like behavior: find existing by title + user (current user)
                $user = auth('api')->user();

                $existing = Task::where('user_id', $user->id)->where('title', $title)->first();

                if ($existing) {
                    $existing->update($data);
                } else {
                    $this->taskService->createTask($data, $user);
                    $inserted++;
                }
            }

            return response()->json(['inserted' => $inserted], 200);
        } catch (\Exception $e) {
            Log::error('Error populating tasks', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error populating tasks'], 500);
        }
    }
}
