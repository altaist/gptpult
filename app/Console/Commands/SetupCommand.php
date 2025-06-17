<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup {--force : Пропустить подтверждение}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Настройка приложения: запуск миграций и сидеров';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Настройка приложения');
        $this->line('');
        
        if (!$this->option('force')) {
            $this->warn('⚠️  ВНИМАНИЕ! Эта команда удалит ВСЕ данные из базы данных!');
            $this->warn('   Все таблицы будут пересозданы заново.');
            $this->line('');

            if (!$this->confirm('Вы уверены, что хотите продолжить?', false)) {
                $this->info('❌ Операция отменена.');
                return Command::FAILURE;
            }
        } else {
            $this->warn('🔥 Принудительный режим: подтверждение пропущено');
        }

        $this->line('');
        $this->info('🚀 Начинаем настройку приложения...');

        // Запускаем миграции
        $this->info('📦 Запуск миграций...');
        $this->call('migrate:fresh');

        // Запускаем сидеры
        $this->info('🌱 Запуск сидеров...');
        $this->call('db:seed');

        $this->info('✅ Настройка приложения завершена!');
        $this->line('');
        $this->info('👤 Созданы пользователи:');
        $this->line('   Администратор: admin@example.com');
        $this->line('   Пользователь: user@example.com');
        $this->line('   Пароль для всех: password');
        $this->line('');
        $this->info('📄 Созданы типы документов:');
        $this->line('   - Реферат');
        $this->line('   - Отчет о практике');
        $this->line('   - Эссе');

        return Command::SUCCESS;
    }
}
