<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:make-user {email} {--create : Создать пользователя если не существует}';

    /**
     * The console command description.
     */
    protected $description = 'Назначить пользователя администратором по email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $shouldCreate = $this->option('create');

        // Проверяем существует ли пользователь
        $user = User::where('email', $email)->first();

        if (!$user) {
            if ($shouldCreate) {
                // Создаем нового пользователя
                $name = $this->ask('Введите имя пользователя');
                $password = $this->secret('Введите пароль для нового пользователя');

                if (strlen($password) < 8) {
                    $this->error('Пароль должен содержать минимум 8 символов');
                    return 1;
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role_id' => UserRole::ADMIN,
                    'status' => 1,
                    'balance_rub' => 0,
                ]);

                $this->info("✅ Создан новый администратор:");
            } else {
                $this->error("❌ Пользователь с email '{$email}' не найден.");
                $this->info("💡 Используйте флаг --create для создания нового пользователя:");
                $this->info("   php artisan admin:make-user {$email} --create");
                return 1;
            }
        } else {
            // Назначаем существующего пользователя администратором
            if ($user->role_id === UserRole::ADMIN) {
                $this->info("ℹ️  Пользователь '{$user->name}' уже является администратором");
                return 0;
            }

            $user->update(['role_id' => UserRole::ADMIN]);
            $this->info("✅ Пользователь '{$user->name}' назначен администратором:");
        }

        // Выводим информацию о пользователе
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['ID', $user->id],
                ['Имя', $user->name],
                ['Email', $user->email],
                ['Роль', $user->role_id->label()],
                ['Статус', $user->status],
                ['Баланс', $user->balance_rub . ' ₽'],
                ['Создан', $user->created_at->format('d.m.Y H:i')],
            ]
        );

        $this->info("🚀 Теперь пользователь может войти в админ-панель по адресу: /admin");

        return 0;
    }
} 