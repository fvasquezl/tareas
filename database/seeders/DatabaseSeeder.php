<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Crear tareas de ejemplo para el usuario
        Task::factory(10)->create([
            'user_id' => $user->id,
        ]);

        // Crear algunas tareas completadas
        Task::factory(5)->completed()->create([
            'user_id' => $user->id,
        ]);

        // Crear tareas de alta prioridad
        Task::factory(3)->highPriority()->create([
            'user_id' => $user->id,
        ]);
    }
}
