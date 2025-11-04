<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Task Factory
 * 
 * Factory for creating Task model instances for testing and seeding.
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a UUID for the primary key 'id' and other attributes.
        return [
            'id' => (string) Str::uuid(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'is_completed' => $this->faker->boolean(20),
            'priority' => $this->faker->randomElement(['low','medium','high']),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the task is completed.
     *
     * @return static
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
        ]);
    }

    /**
     * Indicate that the task is not completed.
     *
     * @return static
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => false,
        ]);
    }
}
