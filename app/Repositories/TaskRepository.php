<?php

namespace App\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repositorio de Tareas
 *
 * Maneja todas las operaciones de base de datos del modelo Task.
 * Proporciona una capa de abstracción para el acceso a datos.
 */
class TaskRepository
{
    /**
     * Obtiene todas las tareas con filtros opcionales.
     *
     * @param int|null $userId Filtra por ID de usuario
     * @param bool|null $isCompleted Filtra por estado de completado
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
     * Obtiene tareas paginadas.
     *
     * @param int $perPage Cantidad de elementos por página
     * @param int|null $userId Filtra por ID de usuario
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, ?int $userId = null, array $filters = [], int $page = 1): LengthAwarePaginator
    {
        $query = Task::with('user');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        // Filtros opcionales
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['completed'])) {
            // Acepta cadenas 'true'/'false', 1/0 o booleanos
            $completed = $filters['completed'];
            if (is_string($completed)) {
                $completed = in_array(strtolower($completed), ['1', 'true', 'yes'], true);
            }
            $query->where('is_completed', (bool) $completed);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Busca una tarea por ID.
     *
    * @param string $id
     * @return Task|null
     */
    public function find(string $id): ?Task
    {
        return Task::with('user')->find($id);
    }

    /**
     * Busca una tarea por ID o lanza excepción.
     *
    * @param string $id
     * @return Task
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): Task
    {
        return Task::with('user')->findOrFail($id);
    }

    /**
     * Crea una tarea nueva.
     *
     * @param array $data
     * @return Task
     */
    public function create(array $data): Task
    {
        // Asegura que haya un UUID como clave primaria al crear la tarea
        if (empty($data['id'])) {
            $data['id'] = (string) \Illuminate\Support\Str::uuid();
        }

        return Task::create($data);
    }

    /**
     * Actualiza una tarea.
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
     * Elimina una tarea.
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
     * Marca una tarea como completada.
     *
     * @param Task $task
     * @return bool
     */
    public function markAsCompleted(Task $task): bool
    {
        return $task->update(['is_completed' => true]);
    }

    /**
     * Marca una tarea como incompleta.
     *
     * @param Task $task
     * @return bool
     */
    public function markAsIncomplete(Task $task): bool
    {
        return $task->update(['is_completed' => false]);
    }

    /**
     * Obtiene tareas por ID de usuario.
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
