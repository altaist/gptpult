<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Jobs\StartFullGenerateDocument;
use Illuminate\Support\Facades\Log;

class TestFullGenerationWithDetailedLogging extends Command
{
    protected $signature = 'test:full-generation-detailed {document_id}';
    protected $description = 'Тестирование полной генерации документа с максимально подробным логгированием';

    public function handle()
    {
        $documentId = $this->argument('document_id');
        
        $this->info("=== ТЕСТИРОВАНИЕ ПОЛНОЙ ГЕНЕРАЦИИ С ДЕТАЛЬНЫМ ЛОГГИРОВАНИЕМ ===");
        $this->info("Документ ID: {$documentId}");
        $this->info("Лог файл: storage/logs/full_generation.log");
        $this->info("Дублирование в: storage/logs/queue.log");
        $this->info("===============================================================");

        try {
            $document = Document::findOrFail($documentId);
            
            $this->info("Документ найден:");
            $this->info("- Название: {$document->title}");
            $this->info("- Статус: {$document->status->value}");
            $this->info("- Thread ID: " . ($document->thread_id ?? 'не установлен'));
            $this->info("- Имеет структуру: " . (empty($document->structure) ? 'НЕТ' : 'ДА'));
            
            if (!empty($document->structure)) {
                $contents = $document->structure['contents'] ?? [];
                $this->info("- Количество топиков: " . count($contents));
                
                $totalSubtopics = 0;
                foreach ($contents as $topic) {
                    $totalSubtopics += count($topic['subtopics'] ?? []);
                }
                $this->info("- Общее количество подразделов: {$totalSubtopics}");
            }
            
            $this->info("");
            $this->info("Запускаем полную генерацию...");
            
            // Очищаем лог файл перед началом
            $logFile = storage_path('logs/full_generation.log');
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                $this->info("Лог файл очищен");
            }
            
            // Запускаем job
            StartFullGenerateDocument::dispatch($document);
            
            $this->info("Job запущен в очереди document_creates");
            $this->info("");
            $this->info("Для мониторинга выполнения используйте:");
            $this->info("tail -f storage/logs/full_generation.log");
            $this->info("");
            $this->info("Или для просмотра только ошибок:");
            $this->info("tail -f storage/logs/full_generation.log | grep -i ошибка");
            
        } catch (\Exception $e) {
            $this->error("Ошибка при запуске тестирования: " . $e->getMessage());
            $this->error("Файл: " . $e->getFile() . ":" . $e->getLine());
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
} 