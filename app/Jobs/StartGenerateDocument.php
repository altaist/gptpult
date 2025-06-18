<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\GptRequestCompleted;
use App\Events\GptRequestFailed;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartGenerateDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Document $document
    ) {
        // Устанавливаем специальную очередь для генерации документов
        $this->onQueue('document_creates');
    }

    /**
     * Execute the job.
     */
    public function handle(GptServiceFactory $factory): void
    {
        try {
            Log::channel('queue')->info('Начало генерации документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'job_id' => $this->job->getJobId()
            ]);

            // Обновляем статус документа на "pre_generating" (если еще не установлен)
            if ($this->document->status !== DocumentStatus::PRE_GENERATING) {
                $this->document->update(['status' => DocumentStatus::PRE_GENERATING]);
            }

            // Получаем настройки GPT из документа
            $gptSettings = $this->document->gpt_settings ?? [];
            $service = $gptSettings['service'] ?? 'openai';
            $model = $gptSettings['model'] ?? 'gpt-3.5-turbo';
            $temperature = $gptSettings['temperature'] ?? 0.7;

            // Получаем сервис из фабрики
            $gptService = $factory->make($service);

            // Формируем промпт для генерации документа
            $prompt = $this->buildPrompt();

            Log::channel('queue')->info('Отправляем запрос к GPT ассистенту', [
                'document_id' => $this->document->id,
                'service' => $service,
                'assistant_id' => 'asst_OwXAXycYmcU85DAeqShRkhYa'
            ]);

            // Работа с OpenAI Assistants API
            $assistantId = 'asst_OwXAXycYmcU85DAeqShRkhYa';
            
            // Создаем thread (не сохраняем в БД)
            $thread = $gptService->createThread();
            
            // Добавляем сообщение в thread
            $gptService->addMessageToThread($thread['id'], $prompt);
            
            // Запускаем run с ассистентом
            $run = $gptService->createRun($thread['id'], $assistantId);
            
            // Ждем завершения run
            $completedRun = $gptService->waitForRunCompletion($thread['id'], $run['id']);
            
            // Получаем ответ
            $response = $gptService->getThreadMessages($thread['id']);
            
            // Извлекаем последнее сообщение ассистента
            $assistantMessage = null;
            foreach ($response['data'] as $message) {
                if ($message['role'] === 'assistant') {
                    $assistantMessage = $message['content'][0]['text']['value'];
                    break;
                }
            }
            
            if (!$assistantMessage) {
                throw new \Exception('Не получен ответ от ассистента');
            }

            // Парсим ответ и извлекаем contents и objectives
            $parsedData = $this->parseGptResponse($assistantMessage);

            // Обновляем структуру документа
            $structure = $this->document->structure ?? [];
            $structure['contents'] = $parsedData['contents'] ?? [];
            $structure['objectives'] = $parsedData['objectives'] ?? [];

            // Сохраняем изменения
            $this->document->update([
                'structure' => $structure,
                'status' => DocumentStatus::PRE_GENERATED
            ]);

            Log::channel('queue')->info('Документ успешно сгенерирован', [
                'document_id' => $this->document->id,
                'contents_count' => count($structure['contents']),
                'objectives_count' => count($structure['objectives']),
                'tokens_used' => $response['tokens_used'] ?? 0
            ]);

            // Создаем фиктивный GptRequest для совместимости с существующими событиями
            $gptRequest = new \App\Models\GptRequest([
                'document_id' => $this->document->id,
                'prompt' => $prompt,
                'response' => $assistantMessage,
                'status' => 'completed',
                'metadata' => [
                    'service' => $service,
                    'assistant_id' => $assistantId,
                    'thread_id' => $thread['id'],
                    'run_id' => $run['id'],
                    'temperature' => $temperature,
                ]
            ]);
            $gptRequest->document = $this->document;

            event(new GptRequestCompleted($gptRequest));

        } catch (\Exception $e) {
            Log::channel('queue')->error('Ошибка при генерации документа', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->document->update([
                'status' => DocumentStatus::PRE_GENERATION_FAILED
            ]);

            // Создаем фиктивный GptRequest для события ошибки
            $gptRequest = new \App\Models\GptRequest([
                'document_id' => $this->document->id,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $gptRequest->document = $this->document;

            event(new GptRequestFailed($gptRequest, $e->getMessage()));

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('queue')->error('Job генерации документа завершился с ошибкой', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->document->update([
            'status' => DocumentStatus::PRE_GENERATION_FAILED
        ]);

        // Создаем фиктивный GptRequest для события ошибки
        $gptRequest = new \App\Models\GptRequest([
            'document_id' => $this->document->id,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
        $gptRequest->document = $this->document;

        event(new GptRequestFailed($gptRequest, $exception->getMessage()));
    }

    /**
     * Формирует промпт для генерации документа
     */
    private function buildPrompt(): string
    {
        $topic = $this->document->structure['topic'] ?? $this->document->title;
        $documentType = $this->document->documentType->name ?? 'документ';
        $pagesNum = $this->document->pages_num ?? 'не указан';

        return "
        {$documentType}, {$topic}, {$pagesNum}
        ";
    }

    /**
     * Парсит ответ от GPT и извлекает структурированные данные
     */
    private function parseGptResponse(string $response): array
    {
        // Пытаемся найти JSON в ответе
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
        
        // Валидируем структуру данных
        if (!isset($data['contents']) || !isset($data['objectives'])) {
            throw new \Exception('Неверная структура данных в ответе GPT');
        }
        
        return $data;
    }
} 