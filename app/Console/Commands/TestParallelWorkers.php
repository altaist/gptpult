<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class TestParallelWorkers extends Command
{
    protected $signature = 'debug:parallel-workers {document_id} {--workers=3} {--duration=60} {--delay=2}';
    protected $description = 'Тестирование параллельных воркеров для выявления race conditions';
    
    private $logChannel = 'debug_generation';
    private $workerId;
    private $sharedThreadId;

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $workersCount = (int)$this->option('workers');
        $duration = (int)$this->option('duration');
        $delay = (int)$this->option('delay');

        $this->info("=== ТЕСТИРОВАНИЕ ПАРАЛЛЕЛЬНЫХ ВОРКЕРОВ ===");
        $this->info("Документ: #{$documentId}");
        $this->info("Количество воркеров: {$workersCount}");
        $this->info("Продолжительность: {$duration}с");
        $this->info("Задержка между запросами: {$delay}с");
        $this->info("==========================================");

        try {
            // Получаем документ и подготавливаем thread
            $document = Document::findOrFail($documentId);
            $this->sharedThreadId = $this->prepareSharedThread($document);
            
            $this->info("Используем общий thread: {$this->sharedThreadId}");
            
            // Запускаем мониторинг thread в фоне
            $this->startThreadMonitoring($this->sharedThreadId, $duration + 10);
            
            // Запускаем параллельные воркеры
            $this->startParallelWorkers($documentId, $workersCount, $duration, $delay);
            
            // Ждем завершения всех процессов
            $this->info("Ожидаем завершения всех воркеров...");
            sleep($duration + 5);
            
            // Анализируем результаты
            $this->analyzeResults();
            
        } catch (\Exception $e) {
            $this->error("Ошибка при тестировании: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function prepareSharedThread(Document $document): string
    {
        $this->info("Подготавливаем общий thread для всех воркеров...");
        
        $factory = app(GptServiceFactory::class);
        $gptService = $factory->make('openai');
        
        // Используем существующий thread или создаем новый
        if ($document->thread_id) {
            $threadId = $document->thread_id;
            $this->info("Используем существующий thread: {$threadId}");
        } else {
            $thread = $gptService->createThread();
            $threadId = $thread['id'];
            $document->update(['thread_id' => $threadId]);
            $this->info("Создан новый thread: {$threadId}");
        }
        
        // Логируем начальное состояние
        Log::channel($this->logChannel)->info("Parallel workers test started", [
            'document_id' => $document->id,
            'thread_id' => $threadId,
            'workers_count' => $this->option('workers'),
            'duration' => $this->option('duration'),
            'start_time' => now()->format('Y-m-d H:i:s.v')
        ]);
        
        return $threadId;
    }

    private function startThreadMonitoring(string $threadId, int $duration): void
    {
        $this->info("Запускаем мониторинг thread в фоне...");
        
        $monitorProcess = new SymfonyProcess([
            'php', 'artisan', 'debug:thread-monitor', 
            $threadId, 
            '--interval=2', 
            "--duration={$duration}"
        ]);
        
        $monitorProcess->start();
        
        Log::channel($this->logChannel)->info("Thread monitoring started", [
            'thread_id' => $threadId,
            'monitor_duration' => $duration,
            'monitor_pid' => $monitorProcess->getPid()
        ]);
    }

    private function startParallelWorkers(int $documentId, int $workersCount, int $duration, int $delay): void
    {
        $this->info("Запускаем {$workersCount} параллельных воркеров...");
        
        $processes = [];
        
        for ($i = 1; $i <= $workersCount; $i++) {
            $process = new SymfonyProcess([
                'php', 'artisan', 'debug:worker-simulation',
                (string)$documentId,
                '--worker-id=' . $i,
                '--thread-id=' . $this->sharedThreadId,
                '--duration=' . $duration,
                '--delay=' . $delay
            ]);
            
            $process->start();
            $processes[] = $process;
            
            $this->info("Воркер #{$i} запущен (PID: {$process->getPid()})");
            
            Log::channel($this->logChannel)->info("Worker started", [
                'worker_id' => $i,
                'document_id' => $documentId,
                'thread_id' => $this->sharedThreadId,
                'process_pid' => $process->getPid(),
                'duration' => $duration,
                'delay' => $delay
            ]);
            
            // Небольшая задержка между запуском воркеров
            usleep(500000); // 0.5 секунды
        }
        
        // Сохраняем информацию о процессах
        $this->storeProcessInfo($processes);
    }

    private function storeProcessInfo(array $processes): void
    {
        $processInfo = [];
        foreach ($processes as $i => $process) {
            $processInfo[] = [
                'worker_id' => $i + 1,
                'pid' => $process->getPid(),
                'started_at' => now()->format('Y-m-d H:i:s.v')
            ];
        }
        
        Log::channel($this->logChannel)->info("All workers started", [
            'total_workers' => count($processes),
            'processes' => $processInfo,
            'shared_thread_id' => $this->sharedThreadId
        ]);
    }

    private function analyzeResults(): void
    {
        $this->info("Анализируем результаты параллельного тестирования...");
        
        $logFile = storage_path('logs/debug_generation.log');
        if (!file_exists($logFile)) {
            $this->warn("Лог файл не найден: {$logFile}");
            return;
        }
        
        // Читаем последние записи лога
        $logLines = $this->getRecentLogLines($logFile, 1000);
        
        // Анализируем конфликты
        $conflicts = $this->analyzeConflicts($logLines);
        $errors = $this->analyzeErrors($logLines);
        $timing = $this->analyzeTiming($logLines);
        
        // Выводим результаты
        $this->displayAnalysisResults($conflicts, $errors, $timing);
    }

    private function getRecentLogLines(string $logFile, int $maxLines): array
    {
        $lines = [];
        $handle = fopen($logFile, 'r');
        if (!$handle) return $lines;
        
        // Читаем последние строки файла
        $buffer = '';
        $pos = -1;
        $lineCount = 0;
        
        fseek($handle, $pos, SEEK_END);
        
        while ($lineCount < $maxLines && ftell($handle) > 0) {
            $char = fgetc($handle);
            if ($char === "\n") {
                if ($buffer !== '') {
                    $lines[] = $buffer;
                    $lineCount++;
                }
                $buffer = '';
            } else {
                $buffer = $char . $buffer;
            }
            fseek($handle, --$pos, SEEK_END);
        }
        
        if ($buffer !== '') {
            $lines[] = $buffer;
        }
        
        fclose($handle);
        return array_reverse($lines);
    }

    private function analyzeConflicts(array $logLines): array
    {
        $conflicts = [];
        $activeRunErrors = 0;
        $threadConflicts = 0;
        
        foreach ($logLines as $line) {
            if (strpos($line, 'while a run') !== false && strpos($line, 'is active') !== false) {
                $activeRunErrors++;
            }
            
            if (strpos($line, 'Активные run') !== false || strpos($line, 'active run') !== false) {
                $threadConflicts++;
            }
        }
        
        return [
            'active_run_errors' => $activeRunErrors,
            'thread_conflicts' => $threadConflicts,
            'total_conflicts' => $activeRunErrors + $threadConflicts
        ];
    }

    private function analyzeErrors(array $logLines): array
    {
        $errors = [];
        $errorCount = 0;
        
        foreach ($logLines as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'error') !== false) {
                $errorCount++;
                if (count($errors) < 5) { // Сохраняем только первые 5 ошибок
                    $errors[] = $line;
                }
            }
        }
        
        return [
            'total_errors' => $errorCount,
            'sample_errors' => $errors
        ];
    }

    private function analyzeTiming(array $logLines): array
    {
        $apiCalls = [];
        $longCalls = 0;
        
        foreach ($logLines as $line) {
            if (strpos($line, 'api_call_time') !== false) {
                // Извлекаем время выполнения
                if (preg_match('/api_call_time.*?(\d+\.\d+)s/', $line, $matches)) {
                    $time = (float)$matches[1];
                    $apiCalls[] = $time;
                    if ($time > 10) {
                        $longCalls++;
                    }
                }
            }
        }
        
        return [
            'total_api_calls' => count($apiCalls),
            'long_calls' => $longCalls,
            'average_time' => count($apiCalls) > 0 ? array_sum($apiCalls) / count($apiCalls) : 0,
            'max_time' => count($apiCalls) > 0 ? max($apiCalls) : 0
        ];
    }

    private function displayAnalysisResults(array $conflicts, array $errors, array $timing): void
    {
        $this->info("\n=== РЕЗУЛЬТАТЫ АНАЛИЗА ===");
        
        // Конфликты
        $this->info("🔴 Конфликты:");
        $this->info("- Ошибки активных run: {$conflicts['active_run_errors']}");
        $this->info("- Конфликты thread: {$conflicts['thread_conflicts']}");
        $this->info("- Всего конфликтов: {$conflicts['total_conflicts']}");
        
        // Ошибки
        $this->info("\n❌ Ошибки:");
        $this->info("- Общее количество ошибок: {$errors['total_errors']}");
        if (!empty($errors['sample_errors'])) {
            $this->info("- Примеры ошибок:");
            foreach (array_slice($errors['sample_errors'], 0, 3) as $error) {
                $this->warn("  " . mb_substr($error, 0, 100) . "...");
            }
        }
        
        // Производительность
        $this->info("\n⏱️ Производительность:");
        $this->info("- Всего API вызовов: {$timing['total_api_calls']}");
        $this->info("- Долгих вызовов (>10s): {$timing['long_calls']}");
        $this->info("- Среднее время: " . number_format($timing['average_time'], 2) . "s");
        $this->info("- Максимальное время: " . number_format($timing['max_time'], 2) . "s");
        
        // Рекомендации
        $this->info("\n💡 Рекомендации:");
        if ($conflicts['total_conflicts'] > 5) {
            $this->warn("- Высокий уровень конфликтов! Рассмотрите использование блокировок");
        }
        if ($timing['long_calls'] > 2) {
            $this->warn("- Много долгих API вызовов. Проверьте таймауты");
        }
        if ($errors['total_errors'] > 10) {
            $this->warn("- Высокий уровень ошибок. Требуется улучшение retry механизма");
        }
        
        // Сохраняем сводный отчет
        $this->saveSummaryReport($conflicts, $errors, $timing);
    }

    private function saveSummaryReport(array $conflicts, array $errors, array $timing): void
    {
        $report = [
            'test_type' => 'parallel_workers',
            'document_id' => $this->argument('document_id'),
            'workers_count' => $this->option('workers'),
            'duration' => $this->option('duration'),
            'thread_id' => $this->sharedThreadId,
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'conflicts' => $conflicts,
            'errors' => $errors,
            'timing' => $timing,
            'recommendations' => $this->generateRecommendations($conflicts, $errors, $timing)
        ];
        
        Log::channel($this->logChannel)->info("Parallel workers test completed", $report);
        
        $this->info("\n📄 Сводный отчет сохранен в лог файл");
    }

    private function generateRecommendations(array $conflicts, array $errors, array $timing): array
    {
        $recommendations = [];
        
        if ($conflicts['total_conflicts'] > 5) {
            $recommendations[] = 'Implement thread locking mechanism';
            $recommendations[] = 'Add exponential backoff for retry attempts';
        }
        
        if ($timing['long_calls'] > 2) {
            $recommendations[] = 'Optimize API call timeouts';
            $recommendations[] = 'Implement asynchronous processing';
        }
        
        if ($errors['total_errors'] > 10) {
            $recommendations[] = 'Improve error handling and retry logic';
            $recommendations[] = 'Add circuit breaker pattern';
        }
        
        return $recommendations;
    }
} 