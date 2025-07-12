<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process as SymfonyProcess;

class StressTestThread extends Command
{
    protected $signature = 'debug:stress-test {document_id} {--workers=5} {--iterations=10} {--no-delay}';
    protected $description = 'Стресс-тестирование thread с максимальной нагрузкой для выявления race conditions';
    
    private $logChannel = 'debug_generation';
    private $threadId;
    private $results = [];

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $workersCount = (int)$this->option('workers');
        $iterations = (int)$this->option('iterations');
        $noDelay = $this->option('no-delay');

        $this->info("=== СТРЕСС-ТЕСТИРОВАНИЕ THREAD ===");
        $this->info("Документ: #{$documentId}");
        $this->info("Количество воркеров: {$workersCount}");
        $this->info("Итераций на воркер: {$iterations}");
        $this->info("Без задержек: " . ($noDelay ? 'ДА' : 'НЕТ'));
        $this->info("Общее количество запросов: " . ($workersCount * $iterations));
        $this->info("=================================");

        try {
            // Подготавливаем thread
            $document = Document::findOrFail($documentId);
            $this->threadId = $this->prepareThread($document);
            
            // Запускаем стресс-тест
            $this->runStressTest($documentId, $workersCount, $iterations, $noDelay);
            
            // Анализируем результаты
            $this->analyzeStressTestResults();
            
        } catch (\Exception $e) {
            $this->error("Ошибка при стресс-тестировании: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function prepareThread(Document $document): string
    {
        $factory = app(GptServiceFactory::class);
        $gptService = $factory->make('openai');
        
        if ($document->thread_id) {
            $threadId = $document->thread_id;
            $this->info("Используем существующий thread: {$threadId}");
        } else {
            $thread = $gptService->createThread();
            $threadId = $thread['id'];
            $document->update(['thread_id' => $threadId]);
            $this->info("Создан новый thread: {$threadId}");
        }
        
        // Очищаем лог перед тестом
        $this->clearDebugLog();
        
        Log::channel($this->logChannel)->info("Stress test started", [
            'document_id' => $document->id,
            'thread_id' => $threadId,
            'workers' => $this->option('workers'),
            'iterations' => $this->option('iterations'),
            'no_delay' => $this->option('no-delay'),
            'total_requests' => $this->option('workers') * $this->option('iterations'),
            'start_time' => now()->format('Y-m-d H:i:s.v')
        ]);
        
        return $threadId;
    }

    private function clearDebugLog(): void
    {
        $logFile = storage_path('logs/debug_generation.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            $this->info("Лог файл очищен для стресс-теста");
        }
    }

    private function runStressTest(int $documentId, int $workersCount, int $iterations, bool $noDelay): void
    {
        $this->info("Запускаем стресс-тест...");
        
        $processes = [];
        $delay = $noDelay ? 0 : 1;
        $duration = ($iterations * $delay) + 30; // Добавляем буферное время
        
        // Запускаем все воркеры одновременно
        for ($i = 1; $i <= $workersCount; $i++) {
            $process = new SymfonyProcess([
                'php', 'artisan', 'debug:stress-worker',
                (string)$documentId,
                '--worker-id=' . $i,
                '--thread-id=' . $this->threadId,
                '--iterations=' . $iterations,
                '--delay=' . $delay
            ]);
            
            $process->start();
            $processes[] = $process;
            
            $this->info("Stress Worker #{$i} запущен (PID: {$process->getPid()})");
            
            // Очень короткая задержка между запуском для максимального stress
            if (!$noDelay) {
                usleep(100000); // 0.1 секунды
            }
        }
        
        // Запускаем интенсивный мониторинг
        $monitorProcess = $this->startIntensiveMonitoring($duration);
        
        // Ждем завершения всех воркеров
        $this->waitForProcesses($processes);
        
        // Останавливаем мониторинг
        if ($monitorProcess && $monitorProcess->isRunning()) {
            $monitorProcess->stop();
        }
        
        $this->info("Все stress воркеры завершены");
    }

    private function startIntensiveMonitoring(int $duration): ?SymfonyProcess
    {
        $this->info("Запускаем интенсивный мониторинг...");
        
        $monitorProcess = new SymfonyProcess([
            'php', 'artisan', 'debug:thread-monitor',
            $this->threadId,
            '--interval=1', // Каждую секунду
            "--duration={$duration}"
        ]);
        
        $monitorProcess->start();
        
        return $monitorProcess;
    }

    private function waitForProcesses(array $processes): void
    {
        $this->info("Ожидаем завершения всех процессов...");
        
        $completedCount = 0;
        $totalCount = count($processes);
        
        while ($completedCount < $totalCount) {
            $completedCount = 0;
            
            foreach ($processes as $i => $process) {
                if (!$process->isRunning()) {
                    $completedCount++;
                    
                    if ($process->getExitCode() !== 0) {
                        $this->warn("Worker #" . ($i + 1) . " завершился с ошибкой");
                        $this->warn("Output: " . $process->getOutput());
                        $this->warn("Error: " . $process->getErrorOutput());
                    }
                }
            }
            
            $this->info("Завершено процессов: {$completedCount}/{$totalCount}");
            
            if ($completedCount < $totalCount) {
                sleep(2);
            }
        }
    }

    private function analyzeStressTestResults(): void
    {
        $this->info("Анализируем результаты стресс-теста...");
        
        sleep(2); // Ждем завершения записи логов
        
        $logFile = storage_path('logs/debug_generation.log');
        if (!file_exists($logFile)) {
            $this->warn("Лог файл не найден");
            return;
        }
        
        $logs = file_get_contents($logFile);
        $logLines = explode("\n", $logs);
        
        // Анализируем результаты
        $analysis = $this->performDetailedAnalysis($logLines);
        
        // Выводим результаты
        $this->displayStressTestResults($analysis);
        
        // Сохраняем итоговый отчет
        $this->saveStressTestReport($analysis);
    }

    private function performDetailedAnalysis(array $logLines): array
    {
        $analysis = [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'active_run_errors' => 0,
            'race_conditions' => 0,
            'timeout_errors' => 0,
            'workers_stats' => [],
            'thread_conflicts' => [],
            'performance_metrics' => [
                'min_time' => PHP_FLOAT_MAX,
                'max_time' => 0,
                'total_time' => 0,
                'api_calls' => 0
            ]
        ];
        
        foreach ($logLines as $line) {
            if (empty($line)) continue;
            
            // Считаем запросы
            if (strpos($line, 'Attempting to add message') !== false) {
                $analysis['total_requests']++;
            }
            
            if (strpos($line, 'Message added successfully') !== false) {
                $analysis['successful_requests']++;
            }
            
            if (strpos($line, 'Failed to add message') !== false) {
                $analysis['failed_requests']++;
            }
            
            // Анализируем типы ошибок
            if (strpos($line, 'while a run') !== false && strpos($line, 'is active') !== false) {
                $analysis['active_run_errors']++;
            }
            
            if (strpos($line, 'race condition') !== false || 
                strpos($line, 'concurrent') !== false) {
                $analysis['race_conditions']++;
            }
            
            if (strpos($line, 'timeout') !== false || strpos($line, 'timed out') !== false) {
                $analysis['timeout_errors']++;
            }
            
            // Анализируем производительность
            if (preg_match('/add_time.*?(\d+\.\d+)s/', $line, $matches)) {
                $time = (float)$matches[1];
                $analysis['performance_metrics']['api_calls']++;
                $analysis['performance_metrics']['total_time'] += $time;
                $analysis['performance_metrics']['min_time'] = min($analysis['performance_metrics']['min_time'], $time);
                $analysis['performance_metrics']['max_time'] = max($analysis['performance_metrics']['max_time'], $time);
            }
            
            // Собираем статистику по воркерам
            if (preg_match('/Worker #(\d+)/', $line, $matches)) {
                $workerId = $matches[1];
                if (!isset($analysis['workers_stats'][$workerId])) {
                    $analysis['workers_stats'][$workerId] = [
                        'requests' => 0,
                        'successes' => 0,
                        'errors' => 0
                    ];
                }
                
                if (strpos($line, 'Attempting to add message') !== false) {
                    $analysis['workers_stats'][$workerId]['requests']++;
                }
                
                if (strpos($line, 'Message added successfully') !== false) {
                    $analysis['workers_stats'][$workerId]['successes']++;
                }
                
                if (strpos($line, 'Failed to add message') !== false) {
                    $analysis['workers_stats'][$workerId]['errors']++;
                }
            }
        }
        
        // Вычисляем средние значения
        if ($analysis['performance_metrics']['api_calls'] > 0) {
            $analysis['performance_metrics']['avg_time'] = 
                $analysis['performance_metrics']['total_time'] / $analysis['performance_metrics']['api_calls'];
        } else {
            $analysis['performance_metrics']['avg_time'] = 0;
        }
        
        if ($analysis['performance_metrics']['min_time'] === PHP_FLOAT_MAX) {
            $analysis['performance_metrics']['min_time'] = 0;
        }
        
        return $analysis;
    }

    private function displayStressTestResults(array $analysis): void
    {
        $this->info("\n=== РЕЗУЛЬТАТЫ СТРЕСС-ТЕСТА ===");
        
        // Общая статистика
        $successRate = $analysis['total_requests'] > 0 ? 
            ($analysis['successful_requests'] / $analysis['total_requests']) * 100 : 0;
        
        $this->info("📊 Общая статистика:");
        $this->info("- Всего запросов: {$analysis['total_requests']}");
        $this->info("- Успешных: {$analysis['successful_requests']}");
        $this->info("- Неуспешных: {$analysis['failed_requests']}");
        $this->info("- Процент успеха: " . number_format($successRate, 2) . "%");
        
        // Типы ошибок
        $this->info("\n🔴 Типы ошибок:");
        $this->info("- Ошибки активных run: {$analysis['active_run_errors']}");
        $this->info("- Race conditions: {$analysis['race_conditions']}");
        $this->info("- Таймауты: {$analysis['timeout_errors']}");
        
        // Производительность
        $this->info("\n⏱️ Производительность:");
        $this->info("- Минимальное время: " . number_format($analysis['performance_metrics']['min_time'], 3) . "s");
        $this->info("- Максимальное время: " . number_format($analysis['performance_metrics']['max_time'], 3) . "s");
        $this->info("- Среднее время: " . number_format($analysis['performance_metrics']['avg_time'], 3) . "s");
        
        // Статистика по воркерам
        $this->info("\n👥 Статистика по воркерам:");
        foreach ($analysis['workers_stats'] as $workerId => $stats) {
            $workerSuccessRate = $stats['requests'] > 0 ? 
                ($stats['successes'] / $stats['requests']) * 100 : 0;
            
            $this->info("- Worker #{$workerId}: {$stats['requests']} запросов, " . 
                       "{$stats['successes']} успешных (" . 
                       number_format($workerSuccessRate, 1) . "%)");
        }
        
        // Рекомендации
        $this->info("\n💡 Рекомендации:");
        
        if ($successRate < 80) {
            $this->warn("- Очень низкий процент успеха! Требуется серьезная оптимизация");
        } elseif ($successRate < 95) {
            $this->warn("- Процент успеха можно улучшить");
        } else {
            $this->info("- Хороший процент успеха");
        }
        
        if ($analysis['active_run_errors'] > 5) {
            $this->warn("- Много ошибок активных run - нужна лучшая синхронизация");
        }
        
        if ($analysis['performance_metrics']['max_time'] > 30) {
            $this->warn("- Есть очень долгие запросы - проверьте таймауты");
        }
    }

    private function saveStressTestReport(array $analysis): void
    {
        $report = [
            'test_type' => 'stress_test',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $this->argument('document_id'),
            'thread_id' => $this->threadId,
            'parameters' => [
                'workers' => $this->option('workers'),
                'iterations' => $this->option('iterations'),
                'no_delay' => $this->option('no-delay'),
                'total_expected_requests' => $this->option('workers') * $this->option('iterations')
            ],
            'results' => $analysis,
            'recommendations' => $this->generateStressTestRecommendations($analysis)
        ];
        
        Log::channel($this->logChannel)->info("Stress test completed", $report);
        
        $this->info("\n📄 Детальный отчет сохранен в лог файл");
    }

    private function generateStressTestRecommendations(array $analysis): array
    {
        $recommendations = [];
        
        $successRate = $analysis['total_requests'] > 0 ? 
            ($analysis['successful_requests'] / $analysis['total_requests']) * 100 : 0;
        
        if ($successRate < 80) {
            $recommendations[] = 'Critical: Success rate is too low - implement robust retry mechanism';
            $recommendations[] = 'Consider implementing distributed locking for thread access';
        }
        
        if ($analysis['active_run_errors'] > 5) {
            $recommendations[] = 'High number of active run errors - improve run status checking';
            $recommendations[] = 'Implement thread-level locking or queuing system';
        }
        
        if ($analysis['performance_metrics']['max_time'] > 30) {
            $recommendations[] = 'Some requests are taking too long - review timeout settings';
            $recommendations[] = 'Consider implementing circuit breaker pattern';
        }
        
        if ($analysis['race_conditions'] > 0) {
            $recommendations[] = 'Race conditions detected - implement proper synchronization';
        }
        
        return $recommendations;
    }
} 