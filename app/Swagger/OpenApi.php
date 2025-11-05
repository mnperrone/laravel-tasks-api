<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Laravel Tasks API",
 *     description="API REST para gestión de tareas — Challenge IT Rock"
 * )
 *
 * @OA\Tag(
 *     name="Tasks",
 *     description="Operaciones CRUD y administración de tareas"
 * )
 *
 * @OA\Tag(
 *     name="Auth",
 *     description="Flujo de autenticación y manejo de tokens JWT"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor local de desarrollo"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Schema(
 *     schema="Task",
 *     description="Entidad Task",
 *     required={"id","title","is_completed","priority","user_id"},
 *     @OA\Property(property="id", type="string", format="uuid", example="6f5cbd4d-9a9e-4d3f-a3e4-33f420c65d7b"),
 *     @OA\Property(property="title", type="string", example="Preparar presentación"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Actualizar slides para la demo"),
 *     @OA\Property(property="is_completed", type="boolean", example=false),
 *     @OA\Property(property="priority", type="string", example="medium"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TaskRequest",
 *     description="Payload para crear o actualizar tareas",
 *     @OA\Property(property="title", type="string", example="Comprar insumos"),
 *     @OA\Property(property="description", type="string", example="Comprar café y snacks"),
 *     @OA\Property(property="is_completed", type="boolean", example=false),
 *     @OA\Property(property="priority", type="string", enum={"low","medium","high"}, example="high")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedTasks",
 *     description="Respuesta paginada estándar de Laravel",
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Task")
 *     ),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="first", type="string", example="http://localhost:8080/api/tasks?page=1"),
 *         @OA\Property(property="last", type="string", example="http://localhost:8080/api/tasks?page=5"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", nullable=true)
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="from", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(property="path", type="string", example="http://localhost:8080/api/tasks"),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="to", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=65)
 *     )
 * )
 */
class OpenApi
{
    // Clase vacía utilizada como ancla para las anotaciones globales de Swagger.
}
