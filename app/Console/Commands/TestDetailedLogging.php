<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestDetailedLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:detailed-logging {document_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование системы детального логгирования очереди';

    /**
     * Execute the console command.
     */
    public function handle(DocumentJobService $documentJobService, TransitionService $transitionService)
    {
        $documentId = $this->argument('document_id');
        
        try {
            $document = Document::findOrFail($documentId);
            
            $this->info("🧪 ТЕСТИРОВАНИЕ СИСТЕМЫ ДЕТАЛЬНОГО ЛОГГИРОВАНИЯ");
            $this->info("📄 Документ: {$document->title} (ID: {$document->id})");
            $this->info("📊 Статус: {$document->status->value}");
            $this->line("");
            
            // Очищаем лог файл для чистого тестирования
            $logFile = storage_path('logs/queue_operations.log');
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                $this->info("🧹 Лог файл очищен");
            }
            
            $this->info("🚀 Запуск тестирования...");
            $this->line("");
            
            // Тестируем систему логгирования
            try {
                $documentJobService->startFullGeneration($document, $transitionService);
                $this->info("✅ Запуск полной генерации выполнен успешно");
            } catch (\Exception $e) {
                $this->warn("⚠️  Ожидаемая ошибка: " . $e->getMessage());
            }
            
            $this->line("");
            $this->info("📊 АНАЛИЗ ЛОГОВ:");
            $this->line("");
            
            // Анализируем логи
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                $lines = explode("\n", $logContent);
                $relevantLines = array_filter($lines, function($line) use ($documentId) {
                    return !empty(trim($line)) && strpos($line, '"document_id":' . $documentId) !== false;
                });
                
                $this->info("📝 Найдено записей в логе: " . count($relevantLines));
                
                if (count($relevantLines) > 0) {
                    $this->line("");
                    $this->info("🔍 Последние события:");
                    
                    foreach (array_slice($relevantLines, -10) as $line) {
                        $this->displayLogLine($line);
                    }
                } else {
                    $this->warn("⚠️  Записи для документа {$documentId} не найдены в логе");
                }
            } else {
                $this->error("❌ Лог файл не найден");
            }
            
            $this->line("");
            $this->info("🔧 КОМАНДЫ ДЛЯ МОНИТОРИНГА:");
            $this->line("");
            $this->line("# Мониторинг в реальном времени:");
            $this->line("php artisan queue:monitor-realtime --document-id={$documentId}");
            $this->line("");
            $this->line("# Просмотр логов:");
            $this->line("tail -f storage/logs/queue_operations.log");
            $this->line("");
            $this->line("# Поиск событий для этого документа:");
            $this->line("grep '\"document_id\":{$documentId}' storage/logs/queue_operations.log");
            $this->line("");
            $this->line("# Проверка дублей:");
            $this->line("php artisan queue:clean-duplicates --document-id={$documentId} --dry-run");
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при тестировании: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function displayLogLine($line)
    {
        // Извлекаем основную информацию из строки лога
        if (preg_match('/\[(.*?)\].*?production\.\w+:\s*(.+)/', $line, $matches)) {
            $time = \Carbon\Carbon::parse($matches[1])->format('H:i:s.v');
            $message = $matches[2];
            
            // Пытаемся извлечь JSON и получить событие
            if (preg_match('/({.*})/', $message, $jsonMatches)) {
                $data = json_decode($jsonMatches[1], true);
                if ($data && isset($data['event'])) {
                    $event = $data['event'];
                    $processId = $data['process_id'] ?? 'N/A';
                    
                    // Цветовое кодирование событий
                    if (strpos($event, 'error') !== false || strpos($event, 'failed') !== false) {
                        $this->line("<fg=red>   {$time} [{$event}] PID:{$processId}</>");
                    } elseif (strpos($event, 'begin') !== false || strpos($event, 'start') !== false) {
                        $this->line("<fg=cyan>   {$time} [{$event}] PID:{$processId}</>");
                    } elseif (strpos($event, 'success') !== false || strpos($event, 'completed') !== false) {
                        $this->line("<fg=green>   {$time} [{$event}] PID:{$processId}</>");
                    } elseif (strpos($event, 'warning') !== false || strpos($event, 'rejected') !== false) {
                        $this->line("<fg=yellow>   {$time} [{$event}] PID:{$processId}</>");
                    } else {
                        $this->line("<fg=blue>   {$time} [{$event}] PID:{$processId}</>");
                    }
                    return;
                }
            }
            
            // Если не удалось извлечь JSON, показываем как есть
            $this->line("   {$time} " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''));
        } else {
            // Если формат не распознан
            $this->line("   " . substr($line, 0, 120) . (strlen($line) > 120 ? '...' : ''));
        }
    }
} 