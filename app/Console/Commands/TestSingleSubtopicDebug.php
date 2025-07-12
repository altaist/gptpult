<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;

class TestSingleSubtopicDebug extends Command
{
    protected $signature = 'debug:single-subtopic {document_id} {--create-new-thread}';
    protected $description = 'Тестирует добавление одного сообщения в thread с максимальным логированием';
    
    private $logChannel = 'debug_generation';
    private $stepCounter = 0;

    public function handle()
    {
        $documentId = $this->argument('document_id');
        $createNewThread = $this->option('create-new-thread');
        
        $this->info("=== НАЧАЛО ДЕБАГ ОДНОГО ПОДРАЗДЕЛА ДЛЯ ДОКУМЕНТА #{$documentId} ===");
        
        try {
            // Получаем документ
            $document = Document::findOrFail($documentId);
            $this->logStep('Документ найден', [
                'document_id' => $document->id,
                'title' => $document->title,
                'thread_id' => $document->thread_id
            ]);

            // Инициализируем GPT сервис
            $factory = app(GptServiceFactory::class);
            $gptService = $factory->make('openai');
            $this->logStep('GPT сервис инициализирован');

            // Получаем или создаем thread
            if ($createNewThread || !$document->thread_id) {
                $this->info("Создаем новый thread...");
                $thread = $gptService->createThread();
                $threadId = $thread['id'];
                $this->logStep('Создан новый thread', ['thread_id' => $threadId]);
                
                if (!$createNewThread) {
                    $document->update(['thread_id' => $threadId]);
                    $this->logStep('Thread ID сохранен в документе');
                }
            } else {
                $threadId = $document->thread_id;
                $this->logStep('Используем существующий thread', ['thread_id' => $threadId]);
            }

            // Проверяем состояние thread
            $this->checkThreadState($gptService, $threadId, 'В начале теста');

            // Создаем тестовое сообщение
            $testMessage = "Тестовое сообщение для дебага. Время: " . now()->format('Y-m-d H:i:s') . "\n\n" .
                          "Напиши краткий ответ (2-3 предложения) на тему: 'Важность тестирования в разработке ПО'";
            
            $this->logStep('Подготовлено тестовое сообщение', [
                'message_length' => mb_strlen($testMessage),
                'message_preview' => mb_substr($testMessage, 0, 100) . '...'
            ]);

            // Тестируем добавление сообщения с детальным логированием
            $this->info("Тестируем добавление сообщения в thread...");
            $messageResult = $this->safeAddMessageWithDetailedLogging($gptService, $threadId, $testMessage);

            // Проверяем состояние thread после добавления сообщения
            $this->checkThreadState($gptService, $threadId, 'После добавления сообщения');

            // Создаем run для теста
            $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';
            $this->info("Создаем run...");
            $run = $gptService->createRun($threadId, $assistantId);
            $this->logStep('Run создан', [
                'run_id' => $run['id'],
                'status' => $run['status']
            ]);

            // Проверяем состояние thread после создания run
            $this->checkThreadState($gptService, $threadId, 'После создания run');

            // Ждем завершения run
            $this->info("Ждем завершения run...");
            $completedRun = $this->waitForRunWithDetailedLogging($gptService, $threadId, $run['id']);

            // Проверяем состояние thread после завершения run
            $this->checkThreadState($gptService, $threadId, 'После завершения run');

            // Получаем ответ
            $response = $this->getAssistantResponse($gptService, $threadId);
            
            $this->logStep('Ответ получен', [
                'response_length' => mb_strlen($response['content']),
                'usage' => $completedRun['usage'] ?? null
            ]);

            $this->info("=== ТЕСТ ЗАВЕРШЕН УСПЕШНО ===");
            $this->info("Ответ ассистента: " . mb_substr($response['content'], 0, 200) . '...');
            $this->info("Лог файл: storage/logs/debug_generation.log");

        } catch (\Exception $e) {
            $this->logStep('ОШИБКА', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);

            $this->error("ОШИБКА: " . $e->getMessage());
            $this->error("Файл: " . $e->getFile() . ":" . $e->getLine());
            
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function logStep(string $message, array $data = [])
    {
        $this->stepCounter++;
        
        $logData = array_merge([
            'step' => $this->stepCounter,
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ], $data);

        Log::channel($this->logChannel)->info("ШАГ {$this->stepCounter}: {$message}", $logData);
        $this->info("ШАГ {$this->stepCounter}: {$message}");
    }

    private function checkThreadState(OpenAiService $gptService, string $threadId, string $context)
    {
        $this->logStep("Проверка состояния thread: {$context}", [
            'thread_id' => $threadId,
            'context' => $context
        ]);

        try {
            // Получаем все run для thread через рефлексию
            $reflection = new \ReflectionClass($gptService);
            $method = $reflection->getMethod('getHttpClient');
            $method->setAccessible(true);
            $httpClient = $method->invoke($gptService, [
                'OpenAI-Beta' => 'assistants=v2',
            ]);
            
            $response = $httpClient->get("https://api.openai.com/v1/threads/{$threadId}/runs");

            if ($response->successful()) {
                $runs = $response->json();
                $activeRuns = [];
                $allRuns = [];

                foreach ($runs['data'] ?? [] as $run) {
                    $runInfo = [
                        'id' => $run['id'],
                        'status' => $run['status'],
                        'created_at' => $run['created_at'] ?? null,
                        'started_at' => $run['started_at'] ?? null,
                        'completed_at' => $run['completed_at'] ?? null,
                        'cancelled_at' => $run['cancelled_at'] ?? null,
                        'failed_at' => $run['failed_at'] ?? null,
                        'expires_at' => $run['expires_at'] ?? null
                    ];
                    
                    $allRuns[] = $runInfo;

                    if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                        $activeRuns[] = $runInfo;
                    }
                }

                $this->logStep('Состояние thread получено', [
                    'thread_id' => $threadId,
                    'context' => $context,
                    'total_runs' => count($allRuns),
                    'active_runs' => count($activeRuns),
                    'active_runs_details' => $activeRuns,
                    'all_runs' => $allRuns
                ]);

                if (!empty($activeRuns)) {
                    $this->warn("🔴 ВНИМАНИЕ: Найдены активные run в thread!");
                    foreach ($activeRuns as $activeRun) {
                        $this->warn("- Run ID: {$activeRun['id']}, Status: {$activeRun['status']}");
                    }
                } else {
                    $this->info("🟢 Thread свободен от активных run");
                }

                // Дополнительная проверка через hasActiveRuns
                $hasActiveViaMethod = $gptService->hasActiveRuns($threadId);
                $this->logStep('Проверка через hasActiveRuns()', [
                    'thread_id' => $threadId,
                    'has_active_runs' => $hasActiveViaMethod,
                    'matches_manual_check' => (count($activeRuns) > 0) === $hasActiveViaMethod
                ]);

            } else {
                $this->logStep('Не удалось получить состояние thread', [
                    'thread_id' => $threadId,
                    'context' => $context,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }

        } catch (\Exception $e) {
            $this->logStep('Ошибка при проверке состояния thread', [
                'thread_id' => $threadId,
                'context' => $context,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function safeAddMessageWithDetailedLogging(OpenAiService $gptService, string $threadId, string $content)
    {
        $this->logStep('Начинаем безопасное добавление сообщения', [
            'thread_id' => $threadId,
            'content_length' => mb_strlen($content)
        ]);

        $maxRetries = 5;
        $attempts = 0;

        while ($attempts < $maxRetries) {
            $attempts++;
            $this->logStep("Попытка #{$attempts} добавления сообщения", [
                'thread_id' => $threadId,
                'attempt' => $attempts,
                'max_retries' => $maxRetries
            ]);

            try {
                // Детальная проверка активных run
                $hasActiveRuns = $gptService->hasActiveRuns($threadId);
                $this->logStep('Проверка активных run', [
                    'thread_id' => $threadId,
                    'has_active_runs' => $hasActiveRuns,
                    'attempt' => $attempts
                ]);

                if ($hasActiveRuns) {
                    $waitTime = 2 + ($attempts * 2);
                    $this->logStep('Обнаружены активные run, ожидаем', [
                        'thread_id' => $threadId,
                        'wait_time' => $waitTime,
                        'attempt' => $attempts
                    ]);
                    
                    $this->warn("⏳ Ожидаем {$waitTime} секунд из-за активных run...");
                    sleep($waitTime);
                    continue;
                }

                // Логируем перед самим API вызовом
                $this->logStep('Выполняем API вызов addMessageToThread', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts,
                    'timestamp_before_call' => now()->format('Y-m-d H:i:s.v')
                ]);

                $startTime = microtime(true);
                $result = $gptService->addMessageToThread($threadId, $content);
                $endTime = microtime(true);
                
                $this->logStep('Сообщение успешно добавлено', [
                    'thread_id' => $threadId,
                    'message_id' => $result['id'] ?? 'unknown',
                    'attempt' => $attempts,
                    'api_call_time' => ($endTime - $startTime) . 's',
                    'timestamp_after_call' => now()->format('Y-m-d H:i:s.v')
                ]);

                $this->info("✅ Сообщение добавлено успешно за " . number_format($endTime - $startTime, 3) . "s");
                return $result;

            } catch (\Exception $e) {
                $this->logStep('Ошибка при добавлении сообщения', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'is_active_run_error' => (strpos($e->getMessage(), 'while a run') !== false && strpos($e->getMessage(), 'is active') !== false),
                    'timestamp_error' => now()->format('Y-m-d H:i:s.v')
                ]);

                if (strpos($e->getMessage(), 'while a run') !== false && strpos($e->getMessage(), 'is active') !== false) {
                    $waitTime = min(15, 3 + ($attempts * 3));
                    $this->logStep('Ошибка активного run, ожидаем больше', [
                        'thread_id' => $threadId,
                        'wait_time' => $waitTime,
                        'attempt' => $attempts
                    ]);
                    
                    $this->warn("❌ Ошибка активного run, ждем {$waitTime} секунд...");
                    sleep($waitTime);
                    continue;
                }

                // Если это другая ошибка, пробрасываем её
                throw $e;
            }
        }

        throw new \Exception("Не удалось добавить сообщение в thread после {$maxRetries} попыток. Thread может иметь активные run.");
    }

    private function waitForRunWithDetailedLogging(OpenAiService $gptService, string $threadId, string $runId)
    {
        $this->logStep('Начинаем ожидание завершения run', [
            'thread_id' => $threadId,
            'run_id' => $runId
        ]);

        $maxWaitTime = 300; // 5 минут
        $startTime = time();
        $checkCount = 0;

        while (time() - $startTime < $maxWaitTime) {
            $checkCount++;
            $currentTime = time() - $startTime;
            
            try {
                $run = $gptService->getRunStatus($threadId, $runId);
                
                $this->logStep("Проверка статуса run #{$checkCount}", [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'status' => $run['status'],
                    'time_elapsed' => $currentTime . 's',
                    'usage' => $run['usage'] ?? null,
                    'last_error' => $run['last_error'] ?? null
                ]);

                if ($run['status'] === 'completed') {
                    $this->logStep('Run завершен успешно', [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'total_wait_time' => $currentTime . 's',
                        'checks_count' => $checkCount,
                        'usage' => $run['usage'] ?? null
                    ]);
                    
                    $this->info("✅ Run завершен за {$currentTime}s");
                    return $run;
                }

                if (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                    $this->logStep('Run завершился неуспешно', [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'status' => $run['status'],
                        'error' => $run['last_error'] ?? 'Неизвестная ошибка'
                    ]);
                    throw new \Exception("Run завершился со статусом: {$run['status']}. Ошибка: " . json_encode($run['last_error'] ?? 'Неизвестная ошибка'));
                }

                // Ждем перед следующей проверкой
                sleep(2);

            } catch (\Exception $e) {
                $this->logStep('Ошибка при проверке статуса run', [
                    'thread_id' => $threadId,
                    'run_id' => $runId,
                    'error' => $e->getMessage(),
                    'time_elapsed' => $currentTime . 's'
                ]);
                throw $e;
            }
        }

        throw new \Exception("Время ожидания run истекло (>{$maxWaitTime}s)");
    }

    private function getAssistantResponse(OpenAiService $gptService, string $threadId)
    {
        $this->logStep('Получаем ответ ассистента', [
            'thread_id' => $threadId
        ]);

        $messages = $gptService->getThreadMessages($threadId);
        
        $this->logStep('Сообщения thread получены', [
            'thread_id' => $threadId,
            'messages_count' => count($messages['data'] ?? [])
        ]);

        // Находим последнее сообщение ассистента
        $assistantMessage = null;
        foreach ($messages['data'] as $message) {
            if ($message['role'] === 'assistant') {
                $assistantMessage = $message['content'][0]['text']['value'];
                break;
            }
        }

        if (!$assistantMessage) {
            throw new \Exception('Не получен ответ от ассистента');
        }

        $this->logStep('Ответ ассистента получен', [
            'thread_id' => $threadId,
            'response_length' => mb_strlen($assistantMessage)
        ]);

        return [
            'content' => $assistantMessage,
            'raw_response' => $assistantMessage
        ];
    }
} 