<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;

class StressWorker extends Command
{
    protected $signature = 'debug:stress-worker {document_id} {--worker-id=1} {--thread-id=} {--iterations=10} {--delay=0}';
    protected $description = 'Stress воркер для агрессивного тестирования thread';
    
    private $logChannel = 'debug_generation';
    private $workerId;
    private $threadId;
    private $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $this->workerId = $this->option('worker-id');
        $this->threadId = $this->option('thread-id');
        $iterations = (int)$this->option('iterations');
        $delay = (int)$this->option('delay');

        $this->info("[StressWorker #{$this->workerId}] Запуск агрессивного тестирования");
        $this->info("[StressWorker #{$this->workerId}] Итераций: {$iterations}");
        $this->info("[StressWorker #{$this->workerId}] Задержка: {$delay}с");

        try {
            // Инициализация
            $document = Document::findOrFail($documentId);
            $factory = app(GptServiceFactory::class);
            $gptService = $factory->make('openai');

            if (!$this->threadId) {
                $this->threadId = $document->thread_id;
            }

            if (!$this->threadId) {
                throw new \Exception("Thread ID не найден");
            }

            $this->logStressEvent('Stress worker started', [
                'document_id' => $documentId,
                'thread_id' => $this->threadId,
                'iterations' => $iterations,
                'delay' => $delay
            ]);

            // Запускаем агрессивное тестирование
            $this->runAggressiveTest($gptService, $iterations, $delay);

            $this->logStressEvent('Stress worker completed successfully');
            $this->info("[StressWorker #{$this->workerId}] Завершено успешно");

        } catch (\Exception $e) {
            $this->logStressEvent('Stress worker failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error("[StressWorker #{$this->workerId}] Ошибка: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function runAggressiveTest(OpenAiService $gptService, int $iterations, int $delay): void
    {
        $stats = [
            'total_attempts' => 0,
            'successful_adds' => 0,
            'failed_adds' => 0,
            'successful_runs' => 0,
            'failed_runs' => 0,
            'active_run_errors' => 0,
            'total_time' => 0,
            'errors' => []
        ];

        $startTime = microtime(true);

        for ($i = 1; $i <= $iterations; $i++) {
            $iterationStartTime = microtime(true);
            
            $this->info("[StressWorker #{$this->workerId}] Итерация #{$i}/{$iterations}");
            
            $stats['total_attempts']++;
            
            try {
                // Агрессивная попытка добавления сообщения
                $success = $this->aggressiveMessageAdd($gptService, $i);
                
                if ($success) {
                    $stats['successful_adds']++;
                    
                    // Сразу же пытаемся создать run
                    $runSuccess = $this->aggressiveRunCreate($gptService, $i);
                    
                    if ($runSuccess) {
                        $stats['successful_runs']++;
                    } else {
                        $stats['failed_runs']++;
                    }
                } else {
                    $stats['failed_adds']++;
                }
                
            } catch (\Exception $e) {
                $stats['failed_adds']++;
                
                if ($this->isActiveRunError($e->getMessage())) {
                    $stats['active_run_errors']++;
                }
                
                $stats['errors'][] = [
                    'iteration' => $i,
                    'error' => $e->getMessage(),
                    'type' => $this->categorizeError($e->getMessage())
                ];
                
                $this->logStressEvent('Stress iteration failed', [
                    'iteration' => $i,
                    'error' => $e->getMessage(),
                    'error_type' => $this->categorizeError($e->getMessage())
                ]);
            }
            
            $iterationTime = microtime(true) - $iterationStartTime;
            $stats['total_time'] += $iterationTime;
            
            // Минимальная задержка или вообще без нее
            if ($delay > 0) {
                sleep($delay);
            } else {
                // Даже без задержки делаем микропаузу для предотвращения полного захвата CPU
                usleep(100000); // 0.1 секунды
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $stats['total_execution_time'] = $totalTime;
        
        // Логируем финальную статистику
        $this->logStressEvent('Stress worker final stats', $stats);
        
        // Выводим краткую сводку
        $this->displayWorkerSummary($stats);
    }

    private function aggressiveMessageAdd(OpenAiService $gptService, int $iteration): bool
    {
        $message = $this->generateStressMessage($iteration);
        
        $this->logStressEvent('Attempting to add message', [
            'iteration' => $iteration,
            'message_length' => mb_strlen($message),
            'thread_id' => $this->threadId,
            'timestamp_before' => now()->format('Y-m-d H:i:s.v')
        ]);
        
        $startTime = microtime(true);
        
        try {
            // Используем обычный метод добавления для более агрессивного тестирования
            // Это может вызвать больше ошибок, что нам и нужно для стресс-теста
            $result = $gptService->addMessageToThread($this->threadId, $message);
            
            $endTime = microtime(true);
            
            $this->logStressEvent('Message added successfully', [
                'iteration' => $iteration,
                'message_id' => $result['id'] ?? 'unknown',
                'add_time' => ($endTime - $startTime) . 's',
                'timestamp_after' => now()->format('Y-m-d H:i:s.v')
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            
            $this->logStressEvent('Failed to add message', [
                'iteration' => $iteration,
                'error' => $e->getMessage(),
                'add_time' => ($endTime - $startTime) . 's',
                'is_active_run_error' => $this->isActiveRunError($e->getMessage()),
                'timestamp_after' => now()->format('Y-m-d H:i:s.v')
            ]);
            
            throw $e;
        }
    }

    private function aggressiveRunCreate(OpenAiService $gptService, int $iteration): bool
    {
        $this->logStressEvent('Creating run', [
            'iteration' => $iteration,
            'assistant_id' => $this->assistantId,
            'thread_id' => $this->threadId
        ]);
        
        $startTime = microtime(true);
        
        try {
            // Создаем run
            $run = $gptService->createRun($this->threadId, $this->assistantId);
            
            $this->logStressEvent('Run created', [
                'iteration' => $iteration,
                'run_id' => $run['id'],
                'status' => $run['status'],
                'create_time' => (microtime(true) - $startTime) . 's'
            ]);
            
            // Для стресс-теста НЕ ждем завершения run
            // Это создаст больше активных run и поможет выявить проблемы
            
            return true;
            
        } catch (\Exception $e) {
            $this->logStressEvent('Failed to create run', [
                'iteration' => $iteration,
                'error' => $e->getMessage(),
                'create_time' => (microtime(true) - $startTime) . 's'
            ]);
            
            return false;
        }
    }

    private function generateStressMessage(int $iteration): string
    {
        $templates = [
            "Стресс-тест #{iteration}: Проанализируй влияние {topic} на {context}.",
            "Iteration #{iteration}: Объясни как {topic} связано с {context}.",
            "Test #{iteration}: Какие проблемы решает {topic} в области {context}?",
            "Stress #{iteration}: Приведи примеры использования {topic} для {context}.",
            "Check #{iteration}: Каковы перспективы развития {topic} в {context}?"
        ];
        
        $topics = [
            'искусственный интеллект', 'машинное обучение', 'блокчейн',
            'квантовые вычисления', 'интернет вещей', 'большие данные',
            'кибербезопасность', 'облачные технологии', 'автоматизация'
        ];
        
        $contexts = [
            'медицине', 'образовании', 'финансах', 'производстве',
            'транспорте', 'энергетике', 'розничной торговле', 'логистике'
        ];
        
        $template = $templates[($iteration - 1) % count($templates)];
        $topic = $topics[($iteration - 1) % count($topics)];
        $context = $contexts[($iteration - 1) % count($contexts)];
        
        $message = str_replace(
            ['{iteration}', '{topic}', '{context}'],
            [$iteration, $topic, $context],
            $template
        );
        
        $message .= "\n\n[StressWorker #{$this->workerId}, Iteration #{$iteration}]";
        $message .= "\n[PID: " . getmypid() . ", Time: " . now()->format('Y-m-d H:i:s.v') . "]";
        
        return $message;
    }

    private function isActiveRunError(string $error): bool
    {
        return strpos($error, 'while a run') !== false && strpos($error, 'is active') !== false;
    }

    private function categorizeError(string $error): string
    {
        if ($this->isActiveRunError($error)) {
            return 'active_run_error';
        }
        
        if (strpos($error, 'timeout') !== false || strpos($error, 'timed out') !== false) {
            return 'timeout_error';
        }
        
        if (strpos($error, 'rate limit') !== false) {
            return 'rate_limit_error';
        }
        
        if (strpos($error, 'invalid_request') !== false) {
            return 'invalid_request_error';
        }
        
        if (strpos($error, 'connection') !== false) {
            return 'connection_error';
        }
        
        return 'unknown_error';
    }

    private function displayWorkerSummary(array $stats): void
    {
        $successRate = $stats['total_attempts'] > 0 ? 
            ($stats['successful_adds'] / $stats['total_attempts']) * 100 : 0;
        
        $this->info("\n[StressWorker #{$this->workerId}] === СВОДКА ===");
        $this->info("Всего попыток: {$stats['total_attempts']}");
        $this->info("Успешных добавлений: {$stats['successful_adds']}");
        $this->info("Неудачных добавлений: {$stats['failed_adds']}");
        $this->info("Процент успеха: " . number_format($successRate, 1) . "%");
        $this->info("Ошибок активных run: {$stats['active_run_errors']}");
        $this->info("Время выполнения: " . number_format($stats['total_execution_time'], 2) . "s");
        
        if ($successRate < 50) {
            $this->error("❌ Очень низкий процент успеха!");
        } elseif ($successRate < 80) {
            $this->warn("⚠️ Низкий процент успеха");
        } else {
            $this->info("✅ Хороший процент успеха");
        }
    }

    private function logStressEvent(string $event, array $data = []): void
    {
        $logData = array_merge([
            'worker_id' => $this->workerId,
            'worker_type' => 'stress',
            'thread_id' => $this->threadId,
            'event' => $event,
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ], $data);

        Log::channel($this->logChannel)->info("StressWorker #{$this->workerId}: {$event}", $logData);
    }
} 