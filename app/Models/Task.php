<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Modelo Task
 *
 * Representa una tarea del sistema con título, descripción, estado de completado
 * y propiedad.
 *
 * @property string $id (UUID)
 * @property string $title
 * @property string|null $description
 * @property bool $is_completed
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Task extends Model
{
    use HasFactory;

    /**
     * La clave primaria es un UUID, no un entero autoincremental.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Tipo de la clave primaria.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Atributos asignables de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'is_completed',
        'priority',
        'user_id',
        'id',
    ];

    /**
     * Atributos que deben convertirse de tipo.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Hook de arranque para generar el UUID automáticamente al crear.
     */
    protected static function booted(): void
    {
        static::creating(function (self $task) {
            // Asegura que la clave primaria 'id' tenga un UUID para las tareas nuevas
            if (empty($task->{$task->getKeyName()})) {
                $task->{$task->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtiene el usuario dueño de la tarea.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sobrescribe el binding de rutas para evitar consultas con valores que no sean UUID
     * (por ejemplo el literal 'populate'). Si el valor no es un UUID válido, retorna null
     * para impedir que la ruta intente resolverlo como un modelo Task.
     */
    public function resolveRouteBinding($value, $field = null)
    {
    // Patrón básico de UUID v4 (hexadecimal con guiones); se mantiene permisivo para variantes
        if (!is_string($value) || !preg_match('/^[0-9a-fA-F\-]{36}$/', $value)) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
