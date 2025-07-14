<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Запускаем сидеры для справочных данных
        $this->call([
            DocumentTypeSeeder::class,
            DocumentSeeder::class,
        ]);

        // Создаем администратора
        User::factory()->create([
            'name' => 'Администратор',
            'email' => 'admin@example.com',
            'role_id' => UserRole::ADMIN,
        ]);

        // Создаем обычного пользователя
        User::factory()->create([
            'name' => 'Обычный пользователь',
            'email' => 'user@example.com',
            'role_id' => UserRole::USER,
        ]);
    }
}
