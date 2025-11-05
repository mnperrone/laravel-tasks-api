<?php

namespace App\Services;

use App\Events\TaskCreated;
use App\Events\TaskCompleted;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * Servicio de Tareas
 *
 * Contiene la lógica de negocio de las operaciones sobre tareas.
 * Valida la propiedad y administra los eventos vinculados a tareas.
 */
class TaskService
{
    /**
     * Instancia del repositorio de tareas.
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
     * Obtiene todas las tareas de un usuario.
     * Usa caché (Redis) con un TTL de 10 minutos.
     */
    public function getAllForUser(User $user, ?bool $isCompleted = null): Collection
    {
        $suffix = $isCompleted !== null ? 'completed' : 'all';
        $context = [];

        if ($isCompleted !== null) {
            $context['completed'] = $isCompleted ? 'true' : 'false';
        }

        return $this->cacheStore()
            ->tags($this->cacheTags($user->id))
            ->remember(
                $this->cacheKey($user->id, $suffix, $context),
                $this->cacheTtl(),
                fn () => $this->repository->getAll($user->id, $isCompleted)
            );
    }

    /**
     * Obtiene las tareas de un usuario paginadas.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedForUser(User $user, int $perPage = 15, array $filters = [], int $page = 1): LengthAwarePaginator
    {
        $context = array_merge([
            'per_page' => $perPage,
            'page' => $page,
        ], $filters);

        return $this->cacheStore()
            ->tags($this->cacheTags($user->id))
            ->remember(
                $this->cacheKey($user->id, 'paginate', $context),
                $this->cacheTtl(),
                fn () => $this->repository->paginate($perPage, $user->id, $filters, $page)
            );
    }

    /**
     * Busca una tarea por su ID.
     *
     * @param string $id
     * @return Task|null
     */
    public function findById(string $id): ?Task
    {
        return $this->repository->find($id);
    }

    /**
     * Crea una tarea nueva.
     *
     * @param array $data
     * @param User $user
     * @return Task
     */
    public function createTask(array $data, User $user): Task
    {
        $data['user_id'] = $user->id;

        $task = $this->repository->create($data);

    // Dispara el evento TaskCreated
        event(new TaskCreated($task));

        $this->flushTaskCache($user->id);

        return $task;
    }

    /**
     * Actualiza una tarea.
     * Valida que el usuario sea el propietario de la tarea.
     *
     * @param Task $task
     * @param array $data
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateTask(Task $task, array $data, User $user): Task
    {
        // Verifica la propiedad
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->update($task, $data);

        $this->flushTaskCache($task->user_id);

        return $task->fresh();
    }

    /**
     * Elimina una tarea.
     * Valida que el usuario sea el propietario de la tarea.
     *
     * @param Task $task
     * @param User $user
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteTask(Task $task, User $user): bool
    {
        // Verifica la propiedad
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to delete this task.');
        }

        $result = $this->repository->delete($task);

        if ($result) {
            $this->flushTaskCache($task->user_id);
        }

        return $result;
    }

    /**
     * Marca una tarea como completada.
     *
     * @param Task $task
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsCompleted(Task $task, User $user): Task
    {
        // Verifica la propiedad
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->markAsCompleted($task);

    // Dispara el evento TaskCompleted
        event(new TaskCompleted($task->fresh()));

        $this->flushTaskCache($task->user_id);

        return $task->fresh();
    }

    /**
     * Marca una tarea como incompleta.
     *
     * @param Task $task
     * @param User $user
     * @return Task
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function markAsIncomplete(Task $task, User $user): Task
    {
        // Verifica la propiedad
        if (!$this->userOwnsTask($task, $user)) {
            abort(403, 'You do not have permission to update this task.');
        }

        $this->repository->markAsIncomplete($task);

        $this->flushTaskCache($task->user_id);

        return $task->fresh();
    }

    /**
     * Revisa si un usuario es dueño de una tarea.
     *
     * @param Task $task
     * @param User $user
     * @return bool
     */
    protected function userOwnsTask(Task $task, User $user): bool
    {
        return $task->user_id === $user->id;
    }

    /**
     * Obtiene el store de caché que soporta tags (Redis).
     */
    protected function cacheStore(): CacheRepository
    {
        return Cache::store(config('cache.default'));
    }

    /**
     * Construye los tags de caché para el usuario indicado.
     */
    protected function cacheTags(int $userId): array
    {
        return ["tasks:user:{$userId}"];
    }

    /**
     * TTL en segundos para la caché de tareas.
     */
    protected function cacheTtl(): int
    {
        return 600; // 10 minutos
    }

    /**
     * Construye una clave de caché para operaciones de usuario.
     */
    protected function cacheKey(int $userId, string $suffix, array $context = []): string
    {
        ksort($context);

        return sprintf(
            'tasks:user:%d:%s:%s',
            $userId,
            $suffix,
            http_build_query($context)
        );
    }

    /**
     * Limpia todas las entradas en caché asociadas a las tareas del usuario.
     */
    protected function flushTaskCache(int $userId): void
    {
        $store = $this->cacheStore();

        if (method_exists($store, 'tags')) {
            $store->tags($this->cacheTags($userId))->flush();
            return;
        }

        Cache::forget("tasks:user:{$userId}");
    }
}
