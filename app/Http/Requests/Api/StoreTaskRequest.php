<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Task Request
 *
 * Valida los datos para crear una nueva tarea.
 */
class StoreTaskRequest extends FormRequest
{
    /**
     * Determina si la persona usuaria está autorizada para realizar la solicitud.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que aplican a la solicitud.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'is_completed' => 'sometimes|boolean',
            'priority' => 'sometimes|in:low,medium,high',
        ];
    }

    /**
     * Obtiene los mensajes personalizados para errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'title.max' => 'Task title cannot exceed 255 characters',
            'description.max' => 'Task description cannot exceed 5000 characters',
            'is_completed.boolean' => 'Task completion status must be true or false',
            'priority.in' => 'Priority must be one of: low, medium, high',
        ];
    }
}
