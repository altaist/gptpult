<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartDocumentGenerationWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work-documents {--timeout=60}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает воркер для обработки очереди генерации документов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        
        $this->info('Запускается воркер для очереди генерации документов...');
        $this->info("Таймаут: {$timeout} секунд");
        
        // Запускаем воркер для специальной очереди document_creates
        $exitCode = $this->call('queue:work', [
            '--queue' => 'document_creates',
            '--timeout' => $timeout,
            '--tries' => 3,
            '--backoff' => 10,
        ]);
        
        return $exitCode;
    }
} 