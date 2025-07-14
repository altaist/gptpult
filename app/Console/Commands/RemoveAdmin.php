<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;

class RemoveAdmin extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:remove-user {email}';

    /**
     * The console command description.
     */
    protected $description = 'Убрать права администратора у пользователя';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Пользователь с email '{$email}' не найден");
            return 1;
        }

        if ($user->role_id !== UserRole::ADMIN) {
            $this->warn("ℹ️  Пользователь '{$user->name}' не является администратором");
            return 0;
        }

        // Проверяем, что это не последний администратор
        $adminCount = User::where('role_id', UserRole::ADMIN)->count();
        if ($adminCount <= 1) {
            $this->error('❌ Нельзя убрать права у последнего администратора!');
            $this->info('💡 Создайте другого администратора перед удалением текущего');
            return 1;
        }

        // Подтверждение
        if (!$this->confirm("Убрать права администратора у пользователя '{$user->name}' ({$user->email})?")) {
            $this->info('Операция отменена');
            return 0;
        }

        $user->update(['role_id' => UserRole::USER]);

        $this->info("✅ Права администратора убраны у пользователя '{$user->name}'");
        $this->info("ℹ️  Теперь пользователь имеет роль: {$user->role_id->label()}");

        return 0;
    }
} 