<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Database Seeder
 *
 * Seeds the database with initial data including roles, permissions,
 * demo users, and sample tasks.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        // Create roles (idempotent)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create permissions
        $permissions = [
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to admin
        $adminRole->givePermissionTo(Permission::all());

        // Assign limited permissions to user
        $userRole->givePermissionTo(['view tasks', 'create tasks', 'update tasks', 'delete tasks']);

        // Create admin user (idempotent)
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );
        $admin->assignRole('admin');

        // Create demo user (idempotent)
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo User', 'password' => Hash::make('password')]
        );
        $demoUser->assignRole('user');

        // Create sample tasks for demo user
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

        // Create additional tasks for admin
        Task::create([
            'title' => 'Configure server settings',
            'description' => 'Update production server configuration',
            'is_completed' => false,
            'user_id' => $admin->id,
        ]);
    }
}
