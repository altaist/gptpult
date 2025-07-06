<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateEmptyUserCommand extends Command
{
    protected $signature = 'create:empty-user {--name=Тестовый Пустой Пользователь}';
    protected $description = 'Создает пустого пользователя для тестирования логики кнопки выхода';

    public function handle()
    {
        $name = $this->option('name');
        
        $user = User::create([
            'name' => $name,
            'email' => Str::random(10) . '@auto.user',
            'password' => Hash::make(Str::random(16)),
            'auth_token' => Str::random(32),
            'role_id' => 0,
            'status' => 1,
            'balance_rub' => 0,
            'person' => [
                'telegram' => [
                    'auto_created' => true,
                    'created_at' => now()->toISOString(),
                ]
            ],
            'settings' => [],
            'statistics' => []
        ]);

        $this->info("✅ Создан пустой пользователь:");
        $this->line("   ID: {$user->id}");
        $this->line("   Имя: {$user->name}");
        $this->line("   Email: {$user->email}");
        $this->line("   Создан: {$user->created_at}");
        $this->line('');
        $this->info("🧪 Протестируйте командой:");
        $this->line("   php artisan test:logout-button --user-id={$user->id}");
        
        return 0;
    }
} 