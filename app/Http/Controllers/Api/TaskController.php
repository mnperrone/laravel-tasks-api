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
use OpenApi\Annotations as OA;

/**
 * Controlador de tareas.
 *
 * Atiende las operaciones CRUD y acciones adicionales sobre tareas del usuario autenticado.
 *
 * @OA\Tag(
 *     name="Tasks",
 *     description="Gestión de tareas del usuario autenticado"
 * )
 */
class TaskController extends Controller
{
    /**
     * Instancia del servicio de tareas.
     *
     * @var TaskService
     */
    protected TaskService $taskService;

    /**
     * Crea una nueva instancia del controlador.
     *
     * @param TaskService $taskService
     * @return void
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Muestra la lista paginada de tareas del usuario.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     *
     * @OA\Get(
     *     path="/api/tasks",
    *     summary="Lista tareas del usuario autenticado",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Cantidad de registros por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="priority",
     *         in="query",
     *         description="Filtra por prioridad (low, medium, high)",
     *         required=false,
     *         @OA\Schema(type="string", example="medium")
     *     ),
     *     @OA\Parameter(
     *         name="completed",
     *         in="query",
     *         description="Filtra por estado de completado (true/false)",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado paginado de tareas",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedTasks")
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = auth('api')->user();
    // Paginación y filtros opcionales
        $perPage = (int) $request->query('per_page', 15);
        $filters = [];
        $page = (int) $request->query('page', 1);

        if ($request->has('priority')) {
            $filters['priority'] = $request->query('priority');
        }

        if ($request->has('completed')) {
            $filters['completed'] = $request->query('completed');
        }

        $tasks = $this->taskService->getPaginatedForUser($user, $perPage, $filters, $page);

        return TaskResource::collection($tasks);
    }

    /**
     * Crea una nueva tarea para el usuario autenticado.
     *
     * @param StoreTaskRequest $request
     * @return JsonResponse
     *
     * @OA\Post(
     *     path="/api/tasks",
     *     summary="Crea una tarea",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TaskRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tarea creada",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
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
     * Muestra el detalle de una tarea.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Get(
     *     path="/api/tasks/{task}",
     *     summary="Obtiene una tarea",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         description="UUID de la tarea",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos"),
     *     @OA\Response(response=404, description="No encontrada")
     * )
     */
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Actualiza una tarea existente.
     *
     * @param UpdateTaskRequest $request
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Put(
     *     path="/api/tasks/{task}",
     *     summary="Actualiza una tarea",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         description="UUID de la tarea",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TaskRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos"),
     *     @OA\Response(response=422, description="Datos inválidos")
     * )
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->updateTask($task, $request->validated(), $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Elimina una tarea del usuario.
     *
     * @param Task $task
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Delete(
     *     path="/api/tasks/{task}",
     *     summary="Elimina una tarea",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         description="UUID de la tarea",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Tarea eliminada"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos")
     * )
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
     * Marca una tarea como completada.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Post(
     *     path="/api/tasks/{task}/complete",
     *     summary="Marca una tarea como completada",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos")
     * )
     */
    public function complete(Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->markAsCompleted($task, $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Marca una tarea como incompleta.
     *
     * @param Task $task
     * @return TaskResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     *
     * @OA\Post(
     *     path="/api/tasks/{task}/incomplete",
     *     summary="Marca una tarea como incompleta",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarea actualizada",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="Sin permisos")
     * )
     */
    public function incomplete(Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $user = auth('api')->user();
        $updatedTask = $this->taskService->markAsIncomplete($task, $user);

        return new TaskResource($updatedTask);
    }

    /**
     * Sincroniza tareas desde la API externa jsonplaceholder.
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @OA\Get(
     *     path="/api/tasks/populate",
     *     summary="Importa tareas externas",
     *     tags={"Tasks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="X-API-KEY",
     *         in="header",
     *         required=true,
     *         description="Clave de seguridad registrada en API_POPULATE_KEY",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sincronización exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="inserted", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=403, description="API key inválida"),
     *     @OA\Response(response=502, description="Error al consumir el servicio externo"),
     *     @OA\Response(response=500, description="Fallo interno")
     * )
     */
    public function populate(Request $request): JsonResponse
    {
        // La validación de la API key la gestiona ApiKeyAuthMiddleware
        try {
            $response = Http::retry(3, 100)->get('https://jsonplaceholder.typicode.com/todos');

            if (!$response->ok()) {
                Log::error('Failed fetching external tasks', ['status' => $response->status()]);
                return response()->json(['message' => 'Failed to fetch external tasks'], 502);
            }

            $todos = $response->json();

            $user = auth('api')->user();
            $now = now()->toDateTimeString();

            // Prepara las filas para el upsert masivo. Se debe incluir el valor UUID de la clave primaria
            // porque la tabla de tareas usa una clave primaria UUID sin valor por defecto.
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

            // Cuenta las tareas existentes para este usuario con los títulos proporcionados para estimar registros insertados
            $existingCount = Task::where('user_id', $user->id)->whereIn('title', array_unique($titles))->count();

            // Ejecuta el upsert masivo usando user_id + title como clave de conflicto
            Task::upsert($rows, ['user_id', 'title'], ['description', 'is_completed', 'priority', 'updated_at']);

            $inserted = max(0, count($rows) - $existingCount);

            return response()->json(['inserted' => $inserted], 200);
        } catch (\Exception $e) {
            Log::error('Error populating tasks', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error populating tasks'], 500);
        }
    }
}
