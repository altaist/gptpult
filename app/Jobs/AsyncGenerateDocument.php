<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\GptRequestCompleted;
use App\Events\GptRequestFailed;
use App\Models\Document;
use App\Models\GptRequest;
use App\Services\Gpt\GptServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AsyncGenerateDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Экспоненциальный backoff

    public function __construct(
        protected Document $document,
        protected array $options = []
    ) {
        $this->onQueue('document_creates');
    }

    public function handle(GptServiceFactory $factory): void
    {
        $startTime = microtime(true);
        
        try {
            // Безопасная перезагрузка документа
            $this->document = $this->document->fresh() ?? $this->document;
            
            Log::channel('queue')->info('🚀 Начало асинхронной генерации документа', [
                'document_id' => $this->document->id,
                'worker_name' => $this->job->getConnectionName() ?? 'unknown',
                'attempt' => $this->attempts()
            ]);

            // Проверяем блокировку документа
            if ($this->isDocumentLocked()) {
                Log::channel('queue')->info('📋 Документ заблокирован другим worker\'ом', [
                    'document_id' => $this->document->id
                ]);
                $this->release(30); // Повторить через 30 секунд
                return;
            }

            // Устанавливаем блокировку
            $this->lockDocument();

            // Обновляем статус
            $this->document->update(['status' => DocumentStatus::PRE_GENERATING]);

            // Получаем сервис
            $gptSettings = $this->document->gpt_settings ?? [];
            $service = $gptSettings['service'] ?? 'openai';
            $gptService = $factory->make($service);

            // Используем Assistant API с неблокирующим подходом
            $result = $this->processWithAssistant($gptService);

            // Обрабатываем результат
            $this->processResult($result);

            // Измеряем время выполнения
            $executionTime = microtime(true) - $startTime;
            
            Log::channel('queue')->info('✅ Генерация документа завершена успешно', [
                'document_id' => $this->document->id,
                'execution_time' => round($executionTime, 2),
                'tokens_used' => $result['tokens_used'] ?? 0
            ]);

        } catch (\Exception $e) {
            $this->handleError($e);
        } finally {
            // Снимаем блокировку
            $this->unlockDocument();
        }
    }

    /**
     * Проверяет, заблокирован ли документ
     */
    private function isDocumentLocked(): bool
    {
        return Cache::has("document_lock_{$this->document->id}");
    }

    /**
     * Блокирует документ для обработки
     */
    private function lockDocument(): void
    {
        Cache::put("document_lock_{$this->document->id}", true, now()->addMinutes(10));
    }

    /**
     * Разблокирует документ
     */
    private function unlockDocument(): void
    {
        Cache::forget("document_lock_{$this->document->id}");
    }

    /**
     * Обработка с Assistant API с оптимизацией
     */
    private function processWithAssistant($gptService): array
    {
        $assistantId = 'asst_OwXAXycYmcU85DAeqShRkhYa';
        
        // Создаем thread
        $thread = $gptService->createThread();
        $this->document->update(['thread_id' => $thread['id']]);

        // Безопасно добавляем сообщение
        $prompt = $this->buildPrompt();
        $gptService->safeAddMessageToThread($thread['id'], $prompt);

        // Безопасно создаем run
        $run = $gptService->safeCreateRun($thread['id'], $assistantId);

        // Оптимизированное ожидание с переменной задержкой
        $result = $this->waitForRunWithOptimizedPolling($gptService, $thread['id'], $run['id']);

        // Получаем сообщения
        $messages = $gptService->getThreadMessages($thread['id']);
        
        // Находим ответ ассистента
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

        return [
            'content' => $assistantMessage,
            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
            'model' => $result['model'] ?? 'unknown'
        ];
    }

    /**
     * Оптимизированное ожидание с переменной задержкой
     */
    private function waitForRunWithOptimizedPolling($gptService, $threadId, $runId): array
    {
        $maxAttempts = 60; // 5 минут максимум
        $attempts = 0;
        $delays = [2, 3, 5, 5, 10]; // Переменная задержка
        
        while ($attempts < $maxAttempts) {
            $run = $gptService->getRunStatus($threadId, $runId);
            
            if ($run['status'] === 'completed') {
                return $run;
            }
            
            if (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                throw new \Exception("Run failed with status: {$run['status']}");
            }
            
            // Используем переменную задержку
            $delay = $delays[min($attempts, count($delays) - 1)];
            sleep($delay);
            $attempts++;
        }
        
        throw new \Exception('Run timeout: превышено время ожидания');
    }

    /**
     * Создает промпт для генерации
     */
    private function buildPrompt(): string
    {
        $topic = $this->document->topic;
        $additionalInfo = $this->document->additional_info ?? '';
        
        return "Создай структуру документа на тему: {$topic}\n\n" .
               "Дополнительная информация: {$additionalInfo}\n\n" .
               "Верни результат в формате JSON с полями 'contents' и 'objectives'.";
    }

    /**
     * Обрабатывает результат генерации
     */
    private function processResult(array $result): void
    {
        $parsedData = $this->parseGptResponse($result['content']);
        
        $this->document->update([
            'status' => DocumentStatus::PRE_GENERATED,
            'structure' => $parsedData,
            'metadata' => array_merge($this->document->metadata ?? [], [
                'tokens_used' => $result['tokens_used'],
                'model' => $result['model'],
                'generation_time' => now()->toDateTimeString()
            ])
        ]);

        // Создаем фиктивный GptRequest для совместимости с событиями
        $gptRequest = new GptRequest([
            'document_id' => $this->document->id,
            'prompt' => $this->buildPrompt(),
            'response' => $result['content'],
            'status' => 'completed',
            'metadata' => [
                'service' => $result['service'] ?? 'openai',
                'model' => $result['model'],
                'tokens_used' => $result['tokens_used'],
                'generation_type' => 'async'
            ]
        ]);
        $gptRequest->document = $this->document;

        event(new GptRequestCompleted($gptRequest));
    }

    /**
     * Парсит ответ от GPT
     */
    private function parseGptResponse(string $response): array
    {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Не удалось найти JSON в ответе GPT');
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Обработка ошибок
     */
    private function handleError(\Exception $e): void
    {
        Log::channel('queue')->error('❌ Ошибка генерации документа', [
            'document_id' => $this->document->id,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts()
        ]);

        $this->document->update([
            'status' => DocumentStatus::PRE_GENERATION_FAILED,
            'error_message' => $e->getMessage()
        ]);

        // Создаем фиктивный GptRequest для события ошибки
        $gptRequest = new GptRequest([
            'document_id' => $this->document->id,
            'status' => 'failed',
            'error_message' => $e->getMessage(),
        ]);
        $gptRequest->document = $this->document;

        event(new GptRequestFailed($gptRequest, $e->getMessage()));
    }

    /**
     * Действия при неудачной попытке
     */
    public function failed(\Exception $exception): void
    {
        $this->unlockDocument();
        
        Log::channel('queue')->error('💥 Генерация документа окончательно провалена', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
} 