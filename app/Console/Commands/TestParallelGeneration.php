<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentType;
use App\Jobs\AsyncGenerateDocument;
use App\Jobs\BatchGenerateDocuments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class TestParallelGeneration extends Command
{
    protected $signature = 'test:parallel-generation {--count=3 : Количество документов для генерации} {--user-id=1 : ID пользователя} {--batch : Использовать batch jobs} {--topic=Тестовая тема : Тема документов}';

    protected $description = 'Тестирование параллельной генерации документов';

    public function handle()
    {
        $count = (int) $this->option('count');
        $userId = (int) $this->option('user-id');
        $useBatch = $this->option('batch');
        $topic = $this->option('topic');

        $this->info("🧪 Тестирование параллельной генерации");
        $this->info("📊 Количество документов: {$count}");
        $this->info("👤 Пользователь: {$userId}");
        $this->info("🗂️ Тема: {$topic}");
        $this->info("⚙️ Режим: " . ($useBatch ? 'Batch Jobs' : 'Индивидуальные Jobs'));
        $this->line('');

        // Проверяем пользователя
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Пользователь с ID {$userId} не найден");
            return 1;
        }

        // Получаем тип документа
        $documentType = DocumentType::first();
        if (!$documentType) {
            $this->error("❌ Не найден тип документа");
            return 1;
        }

        // Создаем тестовые документы
        $documents = [];
        $documentIds = [];
        
        $this->info("📝 Создание тестовых документов...");
        for ($i = 1; $i <= $count; $i++) {
            $document = Document::create([
                'user_id' => $userId,
                'document_type_id' => $documentType->id,
                'title' => "{$topic} #{$i}",
                'structure' => ['topic' => "{$topic} #{$i}"],
                'status' => 'draft',
                'gpt_settings' => [
                    'service' => 'openai',
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => 0.7
                ]
            ]);
            
            $documents[] = $document;
            $documentIds[] = $document->id;
            $this->line("   ✅ Документ #" . $i . " создан (ID: " . $document->id . ")");
        }

        $this->line('');
        $startTime = microtime(true);

        if ($useBatch) {
            // Используем batch jobs
            $this->info("🚀 Запуск batch генерации...");
            
            $batch = Bus::batch([
                new BatchGenerateDocuments($documentIds)
            ])
            ->then(function ($batch) {
                Log::info('Batch генерация завершена', ['batch_id' => $batch->id]);
            })
            ->catch(function ($batch, \Throwable $e) {
                Log::error('Batch генерация провалена', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage()
                ]);
            })
            ->name('test-parallel-generation')
            ->onQueue('document_creates')
            ->dispatch();

            $this->info("✅ Batch создан с ID: {$batch->id}");
            
        } else {
            // Используем индивидуальные jobs
            $this->info("🚀 Запуск индивидуальных jobs...");
            
            foreach ($documents as $i => $document) {
                AsyncGenerateDocument::dispatch($document);
                $this->line("   ✅ Job #" . ($i + 1) . " запущен для документа " . $document->id);
            }
        }

        $executionTime = microtime(true) - $startTime;
        
        $this->line('');
        $this->info("✅ Все задачи запущены за " . round($executionTime, 2) . " секунд");
        $this->line('');
        
        // Показываем команды для мониторинга
        $this->info("📋 Команды для мониторинга:");
        $this->line('');
        $this->line("🔄 Запуск worker'ов (в отдельных терминалах):");
        $this->line("   php artisan queue:work-parallel --workers=3");
        $this->line("   # или");
        $this->line("   php artisan queue:work-documents");
        $this->line('');
        $this->line("📊 Проверка статуса документов:");
        foreach ($documentIds as $id) {
            $this->line("   curl -s http://localhost/documents/{$id}/status | jq '.status'");
        }
        $this->line('');
        $this->line("📋 Мониторинг очереди:");
        $this->line("   php artisan queue:monitor document_creates");
        $this->line("   watch -n 2 'php artisan queue:size document_creates'");
        $this->line('');
        $this->line("🗂️ Просмотр логов:");
        $this->line("   tail -f storage/logs/queue.log");
        
        if ($useBatch) {
            $this->line('');
            $this->line("📊 Мониторинг batch:");
            $this->line("   php artisan queue:batches");
        }

        return 0;
    }
} 