<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Enums\UserRole;

class ListAdmins extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin:list';

    /**
     * The console command description.
     */
    protected $description = 'Показать список всех администраторов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admins = User::where('role_id', UserRole::ADMIN)->get();

        if ($admins->isEmpty()) {
            $this->warn('❌ Администраторы не найдены');
            $this->info('💡 Создайте администратора командой:');
            $this->info('   php artisan admin:make-user admin@example.com --create');
            return 0;
        }

        $this->info("👑 Список администраторов ({$admins->count()}):");
        
        $tableData = $admins->map(function ($admin) {
            return [
                $admin->id,
                $admin->name,
                $admin->email,
                $admin->status ? '✅ Активен' : '❌ Неактивен',
                $admin->balance_rub . ' ₽',
                $admin->created_at->format('d.m.Y H:i'),
                $admin->telegram_id ? '✅' : '❌'
            ];
        })->toArray();

        $this->table(
            ['ID', 'Имя', 'Email', 'Статус', 'Баланс', 'Создан', 'Telegram'],
            $tableData
        );

        return 0;
    }
} 