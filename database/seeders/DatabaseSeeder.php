<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder principal
 *
 * Pobla la base de datos con datos iniciales: roles, permisos,
 * usuarios demo y tareas de ejemplo.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta el seed de la base de datos de la aplicación.
     *
     * @return void
     */
    public function run(): void
    {
    // Crea los roles (idempotente)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

    // Crea los permisos
        $permissions = [
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

    // Asigna todos los permisos al rol admin
        $adminRole->givePermissionTo(Permission::all());

    // Asigna permisos acotados al rol user
        $userRole->givePermissionTo(['view tasks', 'create tasks', 'update tasks', 'delete tasks']);

    // Crea el usuario admin (idempotente)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

    // Crea un usuario demo (idempotente)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make('password')]
        );
        $demoUser->assignRole('user');

    // Crea tareas de ejemplo para el usuario demo
        Task::create([
            'title' => 'Complete project documentation',
            'description' => 'Write comprehensive documentation for the API endpoints',
            'is_completed' => false,
            'user_id' => $demoUser->id,
        ]);

        Task::create([
            'title' => 'Review pull requests',
            'description' => 'Check and approve pending pull requests',
            'is_completed' => true,
            'user_id' => $demoUser->id,
        ]);

        Task::create([
            'title' => 'Update dependencies',
            'description' => null,
            'is_completed' => false,
            'user_id' => $demoUser->id,
        ]);

    // Crea tareas adicionales para el admin
        Task::create([
            'title' => 'Configure server settings',
            'description' => 'Update production server configuration',
            'is_completed' => false,
            'user_id' => $admin->id,
        ]);
    }
}
