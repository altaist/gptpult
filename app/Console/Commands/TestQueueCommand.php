<?php

namespace App\Console\Commands;

use App\Jobs\TestQueueJob;
use Illuminate\Console\Command;

class TestQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запускает тестовую job в очередь';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Отправляем тестовую job в очередь...');
        
        TestQueueJob::dispatch();
        
        $this->info('Job успешно отправлена в очередь!');
        $this->info('Проверьте логи через 10 секунд для подтверждения выполнения.');
    }
} 