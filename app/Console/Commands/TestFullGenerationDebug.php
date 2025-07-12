<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestFullGenerationDebug extends Command
{
    protected $signature = 'debug:full-generation {document_id}';
    protected $description = 'Тестовый скрипт для дебага полной генерации документа с максимальным логированием';
    
    private $logChannel = 'debug_generation';
    private $startTime;
    private $stepCounter = 0;

    public function handle()
    {
        $this->startTime = now();
        $documentId = $this->argument('document_id');
        
        $this->info("=== НАЧАЛО ДЕБАГ ГЕНЕРАЦИИ ДОКУМЕНТА #{$documentId} ===");
        $this->logStep('Начало дебаг генерации', [
            'document_id' => $documentId,
            'start_time' => $this->startTime->format('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'time_limit' => ini_get('max_execution_time')
        ]);

        try {
            // Получаем документ
            $document = Document::findOrFail($documentId);
            $this->logStep('Документ найден', [
                'document_id' => $document->id,
                'title' => $document->title,
                'status' => $document->status->value,
                'thread_id' => $document->thread_id,
                'structure_exists' => !empty($document->structure),
                'content_exists' => !empty($document->content)
            ]);

            // Проверяем структуру
            $structure = $document->structure;
            if (!$structure || !isset($structure['contents']) || empty($structure['contents'])) {
                throw new \Exception('Нет структуры документа для полной генерации');
            }

            $this->logStep('Структура документа проверена', [
                'topics_count' => count($structure['contents']),
                'total_subtopics' => $this->countSubtopics($structure['contents'])
            ]);

            // Инициализируем GPT сервис
            $factory = app(GptServiceFactory::class);
            $gptService = $factory->make('openai');
            $this->logStep('GPT сервис инициализирован', [
                'service_class' => get_class($gptService),
                'service_name' => $gptService->getName()
            ]);

            // Получаем thread_id
            $threadId = $document->thread_id;
            if (!$threadId) {
                throw new \Exception('Нет thread_id для документа');
            }

            $this->logStep('Thread ID получен', [
                'thread_id' => $threadId
            ]);

            // Проверяем состояние thread перед началом
            $this->checkThreadState($gptService, $threadId, 'Перед началом генерации');

            // Начинаем генерацию
            $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';
            $generatedContent = [
                'title' => $document->title,
                'topics' => []
            ];

            $contents = $structure['contents'];
            foreach ($contents as $topicIndex => $topic) {
                $this->logStep("Начинаем обработку топика #{$topicIndex}", [
                    'topic_title' => $topic['title'],
                    'subtopics_count' => count($topic['subtopics'] ?? [])
                ]);

                $generatedTopic = [
                    'title' => $topic['title'],
                    'subtopics' => []
                ];

                if (!empty($topic['subtopics'])) {
                    foreach ($topic['subtopics'] as $subtopicIndex => $subtopic) {
                        $this->logStep("Генерируем подраздел #{$subtopicIndex}", [
                            'topic_index' => $topicIndex,
                            'subtopic_index' => $subtopicIndex,
                            'subtopic_title' => $subtopic['title']
                        ]);

                        // Детальная проверка состояния thread перед добавлением сообщения
                        $this->checkThreadState($gptService, $threadId, "Перед добавлением сообщения для подраздела #{$subtopicIndex}");

                        // Строим промпт
                        $prompt = $this->buildSubtopicPrompt($subtopic);
                        $this->logStep('Промпт построен', [
                            'prompt_length' => mb_strlen($prompt),
                            'prompt_preview' => mb_substr($prompt, 0, 200) . '...'
                        ]);

                        // Пытаемся добавить сообщение с детальным логированием
                        $messageResult = $this->safeAddMessageWithDetailedLogging($gptService, $threadId, $prompt);
                        
                        // Проверяем состояние thread после добавления сообщения
                        $this->checkThreadState($gptService, $threadId, "После добавления сообщения для подраздела #{$subtopicIndex}");

                        // Создаем run
                        $this->logStep('Создаем run', [
                            'thread_id' => $threadId,
                            'assistant_id' => $assistantId
                        ]);

                        $run = $gptService->createRun($threadId, $assistantId);
                        $this->logStep('Run создан', [
                            'run_id' => $run['id'],
                            'status' => $run['status'],
                            'created_at' => $run['created_at'] ?? null
                        ]);

                        // Ждем завершения run с детальным логированием
                        $completedRun = $this->waitForRunWithDetailedLogging($gptService, $threadId, $run['id']);

                        // Проверяем состояние thread после завершения run
                        $this->checkThreadState($gptService, $threadId, "После завершения run для подраздела #{$subtopicIndex}");

                        // Получаем ответ
                        $response = $this->getAssistantResponse($gptService, $threadId);
                        
                        $generatedTopic['subtopics'][] = [
                            'title' => $subtopic['title'],
                            'content' => $response['content'],
                            'generated_at' => now()->toDateTimeString(),
                            'run_id' => $run['id'],
                            'usage' => $completedRun['usage'] ?? null
                        ];

                        $this->logStep('Подраздел успешно сгенерирован', [
                            'subtopic_title' => $subtopic['title'],
                            'content_length' => mb_strlen($response['content']),
                            'usage' => $completedRun['usage'] ?? null
                        ]);

                        // Пауза между запросами
                        $this->logStep('Пауза между запросами', ['seconds' => 3]);
                        sleep(3);
                    }
                }

                $generatedContent['topics'][] = $generatedTopic;
                $this->logStep('Топик завершен', [
                    'topic_title' => $topic['title'],
                    'subtopics_generated' => count($generatedTopic['subtopics'])
                ]);
            }

            // Финальные логи
            $this->logStep('Генерация завершена успешно', [
                'total_topics' => count($generatedContent['topics']),
                'total_subtopics' => $this->countGeneratedSubtopics($generatedContent['topics']),
                'execution_time' => $this->startTime->diffInSeconds(now()) . ' сек'
            ]);

            $this->info("=== ГЕНЕРАЦИЯ ЗАВЕРШЕНА УСПЕШНО ===");
            $this->info("Общее время выполнения: " . $this->startTime->diffInSeconds(now()) . " сек");
            $this->info("Всего шагов: " . $this->stepCounter);
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
        $timeFromStart = $this->startTime->diffInSeconds(now());
        
        $logData = array_merge([
            'step' => $this->stepCounter,
            'time_from_start' => $timeFromStart . 's',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ], $data);

        Log::channel($this->logChannel)->info("ШАГ {$this->stepCounter}: {$message}", $logData);
        $this->info("ШАГ {$this->stepCounter} (+{$timeFromStart}s): {$message}");
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
                    $allRuns[] = [
                        'id' => $run['id'],
                        'status' => $run['status'],
                        'created_at' => $run['created_at'] ?? null,
                        'started_at' => $run['started_at'] ?? null,
                        'completed_at' => $run['completed_at'] ?? null
                    ];

                    if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                        $activeRuns[] = [
                            'id' => $run['id'],
                            'status' => $run['status'],
                            'created_at' => $run['created_at'] ?? null
                        ];
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
                    $this->warn("ВНИМАНИЕ: Найдены активные run в thread!");
                    foreach ($activeRuns as $activeRun) {
                        $this->warn("- Run ID: {$activeRun['id']}, Status: {$activeRun['status']}");
                    }
                }

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
                // Проверяем активные run
                $hasActiveRuns = $gptService->hasActiveRuns($threadId);
                $this->logStep('Проверка активных run', [
                    'thread_id' => $threadId,
                    'has_active_runs' => $hasActiveRuns,
                    'attempt' => $attempts
                ]);

                if ($hasActiveRuns) {
                    $waitTime = 2 + $attempts;
                    $this->logStep('Обнаружены активные run, ожидаем', [
                        'thread_id' => $threadId,
                        'wait_time' => $waitTime,
                        'attempt' => $attempts
                    ]);
                    sleep($waitTime);
                    continue;
                }

                // Пытаемся добавить сообщение
                $this->logStep('Добавляем сообщение в thread', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts
                ]);

                $result = $gptService->addMessageToThread($threadId, $content);
                
                $this->logStep('Сообщение успешно добавлено', [
                    'thread_id' => $threadId,
                    'message_id' => $result['id'] ?? 'unknown',
                    'attempt' => $attempts
                ]);

                return $result;

            } catch (\Exception $e) {
                $this->logStep('Ошибка при добавлении сообщения', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                    'is_active_run_error' => (strpos($e->getMessage(), 'while a run') !== false && strpos($e->getMessage(), 'is active') !== false)
                ]);

                if (strpos($e->getMessage(), 'while a run') !== false && strpos($e->getMessage(), 'is active') !== false) {
                    $waitTime = min(10, 2 + $attempts * 2);
                    $this->logStep('Ошибка активного run, ожидаем больше', [
                        'thread_id' => $threadId,
                        'wait_time' => $waitTime,
                        'attempt' => $attempts
                    ]);
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
                    return $run;
                }

                if ($run['status'] === 'failed') {
                    $this->logStep('Run завершился с ошибкой', [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'error' => $run['last_error'] ?? 'Неизвестная ошибка'
                    ]);
                    throw new \Exception('Run завершился с ошибкой: ' . json_encode($run['last_error'] ?? 'Неизвестная ошибка'));
                }

                if ($run['status'] === 'expired') {
                    throw new \Exception('Run истек');
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

        // Парсим JSON ответ если он есть
        $contentText = $assistantMessage;
        if (strpos($assistantMessage, '{') !== false) {
            $jsonData = json_decode($assistantMessage, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['text'])) {
                $contentText = $jsonData['text'];
            }
        }

        $this->logStep('Ответ ассистента получен', [
            'thread_id' => $threadId,
            'response_length' => mb_strlen($assistantMessage),
            'parsed_content_length' => mb_strlen($contentText),
            'is_json' => strpos($assistantMessage, '{') !== false
        ]);

        return [
            'content' => $contentText,
            'raw_response' => $assistantMessage
        ];
    }

    private function buildSubtopicPrompt(array $subtopic): string
    {
        return "Напиши подробный раздел на тему: \"{$subtopic['title']}\".\n\n" .
               "Требования:\n" .
               "- Объем: 800-1200 слов\n" .
               "- Структурированный текст с подзаголовками\n" .
               "- Практические примеры и рекомендации\n" .
               "- Профессиональный стиль изложения\n\n" .
               "Верни только текст раздела, без дополнительных комментариев.";
    }

    private function countSubtopics(array $contents): int
    {
        $count = 0;
        foreach ($contents as $topic) {
            $count += count($topic['subtopics'] ?? []);
        }
        return $count;
    }

    private function countGeneratedSubtopics(array $topics): int
    {
        $count = 0;
        foreach ($topics as $topic) {
            $count += count($topic['subtopics'] ?? []);
        }
        return $count;
    }
} 