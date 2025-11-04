<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Task Model
 * 
 * Represents a task in the system with title, description, completion status,
 * and ownership.
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
     * The primary key is a UUID string, not an incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The primary key type.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_completed' => 'boolean',
    ];

    /**
     * Boot model to generate UUID automatically on create.
     */
    protected static function booted(): void
    {
        static::creating(function (self $task) {
            // Ensure the primary key 'id' is populated with a UUID for new tasks
            if (empty($task->{$task->getKeyName()})) {
                $task->{$task->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the task.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
