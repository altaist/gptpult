<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Document;
use Symfony\Component\Process\Process as SymfonyProcess;
use Carbon\Carbon;

class TestMultiDocumentParallel extends Command
{
    protected $signature = 'debug:multi-doc-parallel 
                          {--documents=5 : Number of documents to create/use} 
                          {--workers=3 : Number of parallel workers} 
                          {--iterations=5 : Number of iterations per worker}
                          {--delay=1 : Delay between iterations in seconds}
                          {--create-new : Create new test documents}';

    protected $description = 'Test parallel processing with multiple documents and different threads';

    public function handle()
    {
        $documentsCount = (int) $this->option('documents');
        $workersCount = (int) $this->option('workers');
        $iterations = (int) $this->option('iterations');
        $delay = (int) $this->option('delay');
        $createNew = $this->option('create-new');

        $this->info("🧪 Запуск тестирования многопоточности с несколькими документами");
        $this->info("📄 Документов: {$documentsCount}");
        $this->info("👥 Воркеров: {$workersCount}");
        $this->info("🔄 Итераций на воркера: {$iterations}");
        $this->info("⏱️ Задержка между итерациями: {$delay}с");

        // Подготовка документов
        $documents = $this->prepareDocuments($documentsCount, $createNew);
        
        if (empty($documents)) {
            $this->error("❌ Не удалось подготовить документы для тестирования");
            return 1;
        }

        $this->info("📋 Подготовлено документов: " . count($documents));
        
        // Отображение информации о документах
        $this->displayDocumentsInfo($documents);

        // Запуск теста
        $testId = 'multi_doc_' . date('Ymd_His');
        $logFile = storage_path("logs/parallel_test_results/{$testId}.log");
        
        // Создание директории если не существует
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->logTestStart($testId, $documents, $workersCount, $iterations, $delay);

        // Запуск воркеров
        $processes = [];
        for ($i = 1; $i <= $workersCount; $i++) {
            $documentIds = $this->distributeDocuments($documents, $i, $workersCount);
            
            $process = new SymfonyProcess([
                'php', 'artisan', 'debug:multi-doc-worker',
                '--worker-id=' . $i,
                '--document-ids=' . implode(',', $documentIds),
                '--iterations=' . $iterations,
                '--delay=' . $delay,
                '--test-id=' . $testId
            ]);
            
            // Увеличиваем таймаут для воркеров
            $process->setTimeout(300); // 5 минут
            $process->start();
            $processes[] = $process;
            
            $this->info("🚀 Запущен воркер #{$i} с документами: " . implode(', ', $documentIds));
        }

        // Ожидание завершения всех процессов
        $this->info("⏳ Ожидание завершения всех воркеров...");
        
        foreach ($processes as $i => $process) {
            $process->wait();
            $this->info("✅ Воркер #" . ($i + 1) . " завершен");
        }

        // Анализ результатов
        $this->analyzeResults($testId, $documents, $workersCount);

        return 0;
    }

    private function prepareDocuments(int $count, bool $createNew): array
    {
        if ($createNew) {
            return $this->createTestDocuments($count);
        } else {
            return $this->getExistingDocuments($count);
        }
    }

    private function createTestDocuments(int $count): array
    {
        $this->info("🔧 Создание {$count} тестовых документов...");
        
        $documents = [];
        for ($i = 1; $i <= $count; $i++) {
            $document = Document::create([
                'title' => "Test Multi-Doc Document #{$i}",
                'content' => null,
                'status' => 'draft',
                'thread_id' => null, // Будет создан при первом использовании
                'user_id' => 1, // Предполагаем, что есть пользователь с ID 1
                'document_type_id' => 1, // Используем тип "Реферат"
                'structure' => [
                    'topic' => "Test Multi-Doc Document #{$i}",
                    'theses' => "This is test document #{$i} for multi-document parallel testing. Created at " . now()->toDateTimeString(),
                    'objectives' => [
                        "Тестирование многопоточности для документа #{$i}",
                        "Проверка работы с OpenAI API",
                        "Анализ производительности системы"
                    ],
                    'contents' => [
                        [
                            'title' => "Введение",
                            'subtopics' => [
                                [
                                    'title' => "Цель тестирования",
                                    'content' => "Проверка многопоточной обработки"
                                ]
                            ]
                        ]
                    ],
                    'references' => []
                ],
                'gpt_settings' => [
                    'service' => 'openai',
                    'model' => 'gpt-3.5-turbo',
                    'temperature' => 0.7,
                ],
            ]);
            
            $documents[] = $document;
            $this->info("📄 Создан документ ID: {$document->id}");
        }
        
        return $documents;
    }

    private function getExistingDocuments(int $count): array
    {
        $this->info("🔍 Поиск существующих документов...");
        
        $documents = Document::orderBy('id', 'desc')
            ->take($count)
            ->get()
            ->toArray();
            
        if (count($documents) < $count) {
            $this->warn("⚠️ Найдено только " . count($documents) . " документов из {$count} требуемых");
            
            if ($this->confirm("Создать недостающие документы?")) {
                $missing = $count - count($documents);
                $newDocuments = $this->createTestDocuments($missing);
                $documents = array_merge($documents, $newDocuments);
            }
        }
        
        return $documents;
    }

    private function distributeDocuments(array $documents, int $workerId, int $totalWorkers): array
    {
        $documentIds = array_column($documents, 'id');
        $distributed = [];
        
        // Распределяем документы равномерно между воркерами
        for ($i = $workerId - 1; $i < count($documentIds); $i += $totalWorkers) {
            $distributed[] = $documentIds[$i];
        }
        
        return $distributed;
    }

    private function displayDocumentsInfo(array $documents): void
    {
        $this->info("\n📊 Информация о документах:");
        $this->table(
            ['ID', 'Title', 'Thread ID', 'Status'],
            array_map(function ($doc) {
                return [
                    $doc['id'],
                    substr($doc['title'], 0, 30) . (strlen($doc['title']) > 30 ? '...' : ''),
                    $doc['thread_id'] ?? 'не создан',
                    is_object($doc['status']) ? $doc['status']->value : $doc['status']
                ];
            }, $documents)
        );
    }

    private function logTestStart(string $testId, array $documents, int $workers, int $iterations, int $delay): void
    {
        Log::channel('debug_generation')->info('Multi-document parallel test started', [
            'test_id' => $testId,
            'test_type' => 'multi_document_parallel',
            'timestamp' => now()->toDateTimeString(),
            'parameters' => [
                'documents_count' => count($documents),
                'workers' => $workers,
                'iterations' => $iterations,
                'delay' => $delay,
            ],
            'documents' => array_map(function ($doc) {
                return [
                    'id' => $doc['id'],
                    'title' => $doc['title'],
                    'thread_id' => $doc['thread_id'],
                    'status' => $doc['status']
                ];
            }, $documents)
        ]);
    }

    private function analyzeResults(string $testId, array $documents, int $workersCount): void
    {
        $this->info("\n📈 Анализ результатов тестирования...");
        
        // Анализ логов
        $logFile = storage_path("logs/parallel_test_results/{$testId}.log");
        if (!file_exists($logFile)) {
            $this->warn("⚠️ Файл логов не найден: {$logFile}");
            return;
        }
        
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        
        $stats = [
            'total_operations' => 0,
            'successful_operations' => 0,
            'failed_operations' => 0,
            'active_run_errors' => 0,
            'race_conditions' => 0,
            'workers_stats' => [],
            'documents_stats' => [],
            'thread_conflicts' => []
        ];
        
        foreach ($lines as $line) {
            if (empty($line)) continue;
            
            try {
                $data = json_decode(substr($line, strpos($line, '{') ?: 0), true);
                if (!$data) continue;
                
                $this->processLogEntry($data, $stats);
            } catch (\Exception $e) {
                continue;
            }
        }
        
        $this->displayAnalysis($stats, $documents, $workersCount);
        $this->generateRecommendations($stats);
    }

    private function processLogEntry(array $data, array &$stats): void
    {
        if (!isset($data['event'])) return;
        
        $event = $data['event'];
        $workerId = $data['worker_id'] ?? 'unknown';
        $documentId = $data['document_id'] ?? 'unknown';
        
        // Инициализация статистики воркера
        if (!isset($stats['workers_stats'][$workerId])) {
            $stats['workers_stats'][$workerId] = [
                'operations' => 0,
                'successes' => 0,
                'errors' => 0,
                'documents' => []
            ];
        }
        
        // Инициализация статистики документа
        if (!isset($stats['documents_stats'][$documentId])) {
            $stats['documents_stats'][$documentId] = [
                'operations' => 0,
                'successes' => 0,
                'errors' => 0,
                'workers' => []
            ];
        }
        
        switch ($event) {
            case 'Message added successfully':
                $stats['successful_operations']++;
                $stats['workers_stats'][$workerId]['successes']++;
                $stats['documents_stats'][$documentId]['successes']++;
                break;
                
            case 'Failed to add message':
                $stats['failed_operations']++;
                $stats['workers_stats'][$workerId]['errors']++;
                $stats['documents_stats'][$documentId]['errors']++;
                
                if (isset($data['is_active_run_error']) && $data['is_active_run_error']) {
                    $stats['active_run_errors']++;
                }
                break;
                
            case 'Run created':
                $stats['successful_operations']++;
                $stats['workers_stats'][$workerId]['successes']++;
                $stats['documents_stats'][$documentId]['successes']++;
                break;
                
            case 'Failed to create run':
                $stats['failed_operations']++;
                $stats['workers_stats'][$workerId]['errors']++;
                $stats['documents_stats'][$documentId]['errors']++;
                break;
        }
        
        $stats['total_operations']++;
        $stats['workers_stats'][$workerId]['operations']++;
        $stats['documents_stats'][$documentId]['operations']++;
        
        // Отслеживание документов для каждого воркера
        if (!in_array($documentId, $stats['workers_stats'][$workerId]['documents'])) {
            $stats['workers_stats'][$workerId]['documents'][] = $documentId;
        }
        
        // Отслеживание воркеров для каждого документа
        if (!in_array($workerId, $stats['documents_stats'][$documentId]['workers'])) {
            $stats['documents_stats'][$documentId]['workers'][] = $workerId;
        }
    }

    private function displayAnalysis(array $stats, array $documents, int $workersCount): void
    {
        $successRate = $stats['total_operations'] > 0 
            ? round(($stats['successful_operations'] / $stats['total_operations']) * 100, 2) 
            : 0;
            
        $this->info("\n📊 Общая статистика:");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего операций', $stats['total_operations']],
                ['Успешных операций', $stats['successful_operations']],
                ['Неудачных операций', $stats['failed_operations']],
                ['Процент успеха', $successRate . '%'],
                ['Ошибки активных run', $stats['active_run_errors']],
                ['Конфликты гонки', $stats['race_conditions']],
            ]
        );
        
        $this->info("\n👥 Статистика по воркерам:");
        $workerRows = [];
        foreach ($stats['workers_stats'] as $workerId => $workerStats) {
            $workerSuccessRate = $workerStats['operations'] > 0 
                ? round(($workerStats['successes'] / $workerStats['operations']) * 100, 2) 
                : 0;
                
            $workerRows[] = [
                $workerId,
                $workerStats['operations'],
                $workerStats['successes'],
                $workerStats['errors'],
                $workerSuccessRate . '%',
                implode(', ', $workerStats['documents'])
            ];
        }
        
        $this->table(
            ['Воркер', 'Операций', 'Успешно', 'Ошибок', 'Успех %', 'Документы'],
            $workerRows
        );
        
        $this->info("\n📄 Статистика по документам:");
        $docRows = [];
        foreach ($stats['documents_stats'] as $docId => $docStats) {
            $docSuccessRate = $docStats['operations'] > 0 
                ? round(($docStats['successes'] / $docStats['operations']) * 100, 2) 
                : 0;
                
            $docRows[] = [
                $docId,
                $docStats['operations'],
                $docStats['successes'],
                $docStats['errors'],
                $docSuccessRate . '%',
                implode(', ', $docStats['workers'])
            ];
        }
        
        $this->table(
            ['Документ ID', 'Операций', 'Успешно', 'Ошибок', 'Успех %', 'Воркеры'],
            $docRows
        );
    }

    private function generateRecommendations(array $stats): void
    {
        $this->info("\n💡 Рекомендации:");
        
        $successRate = $stats['total_operations'] > 0 
            ? ($stats['successful_operations'] / $stats['total_operations']) * 100 
            : 0;
            
        if ($successRate > 95) {
            $this->info("✅ Отличная производительность! Система хорошо справляется с многопоточностью");
        } elseif ($successRate > 80) {
            $this->warn("⚠️ Хорошая производительность, но есть место для улучшений");
        } else {
            $this->error("❌ Низкая производительность, требуется оптимизация");
        }
        
        if ($stats['active_run_errors'] > 0) {
            $this->warn("⚠️ Обнаружены ошибки активных run: {$stats['active_run_errors']}");
            $this->info("   Рекомендация: Улучшить механизм проверки статуса run перед операциями");
        }
        
        // Анализ распределения нагрузки
        $workerOperations = array_column($stats['workers_stats'], 'operations');
        $maxOps = max($workerOperations);
        $minOps = min($workerOperations);
        $loadBalance = $maxOps > 0 ? ($minOps / $maxOps) * 100 : 100;
        
        if ($loadBalance < 80) {
            $this->warn("⚠️ Неравномерное распределение нагрузки между воркерами");
            $this->info("   Рекомендация: Пересмотреть алгоритм распределения документов");
        } else {
            $this->info("✅ Хорошее распределение нагрузки между воркерами");
        }
    }
} 