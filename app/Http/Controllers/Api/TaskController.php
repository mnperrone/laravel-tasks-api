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
        // API key validation is handled by ApiKeyAuthMiddleware
        try {
            $response = Http::retry(3, 100)->get('https://jsonplaceholder.typicode.com/todos');

            if (!$response->ok()) {
                Log::error('Failed fetching external tasks', ['status' => $response->status()]);
                return response()->json(['message' => 'Failed to fetch external tasks'], 502);
            }

            $todos = $response->json();

            $user = auth('api')->user();
            $now = now()->toDateTimeString();

            // Prepare rows for bulk upsert. We must include the UUID primary key value
            // because the tasks table uses a UUID primary without default.
            $rows = [];
            $titles = [];

            foreach ($todos as $todo) {
                $title = Str::limit($todo['title'] ?? 'Untitled', 255);
                $titles[] = $title;

                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'title' => $title,
                    'description' => null,
                    'is_completed' => !empty($todo['completed']),
                    'priority' => 'medium',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Count existing tasks for this user with the provided titles to estimate inserted rows
            $existingCount = Task::where('user_id', $user->id)->whereIn('title', array_unique($titles))->count();

            // Perform bulk upsert using user_id + title as conflict target
            Task::upsert($rows, ['user_id', 'title'], ['description', 'is_completed', 'priority', 'updated_at']);

            $inserted = max(0, count($rows) - $existingCount);

            return response()->json(['inserted' => $inserted], 200);
        } catch (\Exception $e) {
            Log::error('Error populating tasks', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error populating tasks'], 500);
        }
    }
}
