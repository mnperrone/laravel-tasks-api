<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Política de Tareas
 *
 * Define la lógica de autorización para operar con tareas.
 * Las personas dueñas pueden actualizar/eliminar sus tareas, el resto solo visualiza.
 */
class TaskPolicy
{
    /**
     * Determina si el usuario puede ver cualquier tarea.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede ver la tarea.
     * Solo la persona dueña o una persona administradora puede ver tareas individuales.
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
     * Determina si el usuario puede crear tareas.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede actualizar la tarea.
     * Solo la persona dueña o una administradora puede actualizar la tarea.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
    // Permite que la persona dueña o una administradora actualice la tarea
        return $user->id === $task->user_id || $user->hasRole('admin');
    }

    /**
     * Determina si el usuario puede eliminar la tarea.
     * Solo la persona dueña o una administradora puede eliminarla.
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
     * Determina si el usuario puede restaurar la tarea.
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
     * Determina si el usuario puede eliminar permanentemente la tarea.
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
