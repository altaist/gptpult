<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class StartDocumentWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work-documents {--timeout=600 : Максимальное время выполнения задачи в секундах}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает воркер очереди для генерации документов (базовой и полной)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        
        $this->info('🚀 Запуск воркера очереди документов...');
        $this->line('');
        $this->line('📋 Очередь: document_creates');
        $this->line("⏰ Таймаут: {$timeout} секунд");
        $this->line('🔄 Поддерживаемые типы генерации:');
        $this->line('   - Базовая генерация (StartGenerateDocument)');
        $this->line('   - Полная генерация (StartFullGenerateDocument)');
        $this->line('');
        $this->line('Для остановки нажмите Ctrl+C');
        $this->line('');
        $this->line('Логи: storage/logs/queue.log');

        try {
            // Запускаем воркер с настройками для очереди документов
            Artisan::call('queue:work', [
                '--queue' => 'document_creates',
                '--timeout' => $timeout,
                '--tries' => 3,
                '--max-time' => 3600, // Максимум 1 час работы воркера
                '--verbose' => true,
            ]);

            $output = Artisan::output();
            $this->line($output);

        } catch (\Exception $e) {
            $this->error('❌ Ошибка при работе воркера: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
} 