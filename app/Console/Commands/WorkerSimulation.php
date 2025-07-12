<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;

class WorkerSimulation extends Command
{
    protected $signature = 'debug:worker-simulation {document_id} {--worker-id=1} {--thread-id=} {--duration=60} {--delay=2}';
    protected $description = 'Симуляция работы одного воркера для параллельного тестирования';
    
    private $logChannel = 'debug_generation';
    private $workerId;
    private $threadId;
    private $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $this->workerId = $this->option('worker-id');
        $this->threadId = $this->option('thread-id');
        $duration = (int)$this->option('duration');
        $delay = (int)$this->option('delay');

        $this->info("[Worker #{$this->workerId}] Запуск симуляции воркера");
        $this->info("[Worker #{$this->workerId}] Документ: #{$documentId}");
        $this->info("[Worker #{$this->workerId}] Thread: {$this->threadId}");
        $this->info("[Worker #{$this->workerId}] Продолжительность: {$duration}с");
        $this->info("[Worker #{$this->workerId}] Задержка: {$delay}с");

        try {
            // Получаем документ и инициализируем сервис
            $document = Document::findOrFail($documentId);
            $factory = app(GptServiceFactory::class);
            $gptService = $factory->make('openai');

            // Если thread_id не передан, используем из документа
            if (!$this->threadId) {
                $this->threadId = $document->thread_id;
                if (!$this->threadId) {
                    throw new \Exception("Thread ID не найден для документа");
                }
            }

            $this->logWorkerEvent('Worker started', [
                'document_id' => $documentId,
                'thread_id' => $this->threadId,
                'duration' => $duration,
                'delay' => $delay
            ]);

            // Основной цикл работы воркера
            $this->runWorkerLoop($gptService, $duration, $delay);

            $this->logWorkerEvent('Worker completed successfully');
            $this->info("[Worker #{$this->workerId}] Работа завершена успешно");

        } catch (\Exception $e) {
            $this->logWorkerEvent('Worker failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error("[Worker #{$this->workerId}] Ошибка: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function runWorkerLoop(OpenAiService $gptService, int $duration, int $delay): void
    {
        $startTime = time();
        $iterationCount = 0;
        $successCount = 0;
        $errorCount = 0;

        while (time() - $startTime < $duration) {
            $iterationCount++;
            $this->info("[Worker #{$this->workerId}] Итерация #{$iterationCount}");

            try {
                // Проверяем состояние thread
                $threadState = $this->checkThreadState($gptService);
                
                // Генерируем уникальное сообщение для воркера
                $message = $this->generateWorkerMessage($iterationCount);
                
                // Пытаемся добавить сообщение в thread
                $this->attemptAddMessage($gptService, $message, $iterationCount);
                
                // Создаем и ожидаем run
                $this->createAndWaitForRun($gptService, $iterationCount);
                
                $successCount++;
                $this->info("[Worker #{$this->workerId}] Итерация #{$iterationCount} завершена успешно");
                
            } catch (\Exception $e) {
                $errorCount++;
                $this->logWorkerEvent('Worker iteration failed', [
                    'iteration' => $iterationCount,
                    'error' => $e->getMessage(),
                    'is_active_run_error' => $this->isActiveRunError($e->getMessage())
                ]);
                
                $this->warn("[Worker #{$this->workerId}] Итерация #{$iterationCount} завершена с ошибкой: " . $e->getMessage());
            }

            // Задержка перед следующей итерацией
            if (time() - $startTime < $duration) {
                $this->info("[Worker #{$this->workerId}] Ожидание {$delay}с перед следующей итерацией...");
                sleep($delay);
            }
        }

        // Логируем статистику работы воркера
        $this->logWorkerEvent('Worker statistics', [
            'total_iterations' => $iterationCount,
            'successful_iterations' => $successCount,
            'failed_iterations' => $errorCount,
            'success_rate' => $iterationCount > 0 ? ($successCount / $iterationCount) * 100 : 0,
            'execution_time' => time() - $startTime
        ]);
    }

    private function checkThreadState(OpenAiService $gptService): array
    {
        $hasActiveRuns = $gptService->hasActiveRuns($this->threadId);
        
        $state = [
            'has_active_runs' => $hasActiveRuns,
            'checked_at' => now()->format('Y-m-d H:i:s.v')
        ];
        
        $this->logWorkerEvent('Thread state checked', $state);
        
        if ($hasActiveRuns) {
            $this->warn("[Worker #{$this->workerId}] ⚠️ Обнаружены активные run в thread");
        }
        
        return $state;
    }

    private function generateWorkerMessage(int $iteration): string
    {
        $messages = [
            "Напиши краткий абзац о важности {topic} в современном мире.",
            "Объясни основные принципы {topic} простыми словами.",
            "Приведи 3 практических примера использования {topic}.",
            "Какие преимущества дает {topic} в бизнесе?",
            "Расскажи о развитии {topic} в будущем."
        ];
        
        $topics = [
            'искусственного интеллекта',
            'облачных технологий',
            'кибербезопасности',
            'больших данных',
            'автоматизации процессов'
        ];
        
        $template = $messages[($iteration - 1) % count($messages)];
        $topic = $topics[($iteration - 1) % count($topics)];
        
        $message = str_replace('{topic}', $topic, $template);
        $message .= "\n\n[Worker #{$this->workerId}, Iteration #{$iteration}, Time: " . now()->format('Y-m-d H:i:s') . "]";
        
        return $message;
    }

    private function attemptAddMessage(OpenAiService $gptService, string $message, int $iteration): array
    {
        $this->logWorkerEvent('Attempting to add message', [
            'iteration' => $iteration,
            'message_length' => mb_strlen($message),
            'thread_id' => $this->threadId
        ]);
        
        $startTime = microtime(true);
        
        try {
            $result = $gptService->safeAddMessageToThread($this->threadId, $message);
            $endTime = microtime(true);
            
            $this->logWorkerEvent('Message added successfully', [
                'iteration' => $iteration,
                'message_id' => $result['id'] ?? 'unknown',
                'add_time' => ($endTime - $startTime) . 's'
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            
            $this->logWorkerEvent('Failed to add message', [
                'iteration' => $iteration,
                'error' => $e->getMessage(),
                'add_time' => ($endTime - $startTime) . 's',
                'is_active_run_error' => $this->isActiveRunError($e->getMessage())
            ]);
            
            throw $e;
        }
    }

    private function createAndWaitForRun(OpenAiService $gptService, int $iteration): array
    {
        $this->logWorkerEvent('Creating run', [
            'iteration' => $iteration,
            'assistant_id' => $this->assistantId
        ]);
        
        $startTime = microtime(true);
        
        // Создаем run
        $run = $gptService->createRun($this->threadId, $this->assistantId);
        
        $this->logWorkerEvent('Run created', [
            'iteration' => $iteration,
            'run_id' => $run['id'],
            'status' => $run['status']
        ]);
        
        // Ждем завершения run
        $completedRun = $gptService->waitForRunCompletion($this->threadId, $run['id']);
        $endTime = microtime(true);
        
        $this->logWorkerEvent('Run completed', [
            'iteration' => $iteration,
            'run_id' => $run['id'],
            'final_status' => $completedRun['status'],
            'total_time' => ($endTime - $startTime) . 's',
            'usage' => $completedRun['usage'] ?? null
        ]);
        
        return $completedRun;
    }

    private function isActiveRunError(string $error): bool
    {
        return strpos($error, 'while a run') !== false && strpos($error, 'is active') !== false;
    }

    private function logWorkerEvent(string $event, array $data = []): void
    {
        $logData = array_merge([
            'worker_id' => $this->workerId,
            'thread_id' => $this->threadId,
            'event' => $event,
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ], $data);

        Log::channel($this->logChannel)->info("Worker #{$this->workerId}: {$event}", $logData);
    }
} 