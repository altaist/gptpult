<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartMultipleWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work-parallel {--workers=3 : Количество worker\'ов} {--timeout=600 : Таймаут для каждого worker\'а}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запуск нескольких worker\'ов для параллельной обработки очереди документов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workers = (int) $this->option('workers');
        $timeout = (int) $this->option('timeout');
        
        $this->info("🚀 Запуск {$workers} worker'ов для параллельной обработки");
        $this->info("⏰ Таймаут: {$timeout} секунд");
        $this->info("📋 Очередь: document_creates");
        $this->line('');
        
        $processes = [];
        
        // Запускаем worker'ы
        for ($i = 1; $i <= $workers; $i++) {
            $process = new Process([
                'php', 
                'artisan', 
                'queue:work',
                '--queue=document_creates',
                "--timeout={$timeout}",
                '--tries=3',
                '--backoff=10',
                '--verbose',
                '--name=worker-' . $i
            ]);
            
            $process->setTimeout(null);
            $process->start();
            
            $processes[$i] = $process;
            $this->info("✅ Worker #{$i} запущен (PID: {$process->getPid()})");
        }
        
        $this->line('');
        $this->info('🎯 Все worker\'ы запущены. Нажмите Ctrl+C для остановки');
        $this->line('');
        
        // Мониторинг процессов
        while (true) {
            $activeWorkers = 0;
            
            foreach ($processes as $id => $process) {
                if ($process->isRunning()) {
                    $activeWorkers++;
                } else {
                    $this->warn("⚠️ Worker #{$id} остановлен. Перезапуск...");
                    
                    // Перезапускаем упавший worker
                    $newProcess = new Process([
                        'php', 
                        'artisan', 
                        'queue:work',
                        '--queue=document_creates',
                        "--timeout={$timeout}",
                        '--tries=3',
                        '--backoff=10',
                        '--verbose',
                        '--name=worker-' . $id
                    ]);
                    
                    $newProcess->setTimeout(null);
                    $newProcess->start();
                    $processes[$id] = $newProcess;
                    
                    $this->info("✅ Worker #{$id} перезапущен (PID: {$newProcess->getPid()})");
                }
            }
            
            $this->line("\r🔄 Активных worker'ов: {$activeWorkers}/{$workers}", false);
            
            sleep(5);
        }
    }
    
    /**
     * Обработка сигнала остановки
     */
    public function __destruct()
    {
        // Останавливаем все процессы при завершении
        foreach ($this->processes ?? [] as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }
} 