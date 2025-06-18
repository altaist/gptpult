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

class StartFullGenerateDocument implements ShouldQueue
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
            // Безопасная перезагрузка документа - игнорируем ошибки подключения к БД
            try {
                $this->document->refresh();
            } catch (\Exception $e) {
                Log::channel('queue')->warning('Не удалось обновить документ из БД, используем текущие данные', [
                    'document_id' => $this->document->id,
                    'error' => $e->getMessage()
                ]);
                // Продолжаем работу с текущими данными документа
            }
            
            Log::channel('queue')->info('Начало полной генерации документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'current_status' => $this->document->status->value,
                'job_id' => $this->job->getJobId()
            ]);

            // Устанавливаем статус "full_generating" если он еще не установлен
            if ($this->document->status !== DocumentStatus::FULL_GENERATING) {
                $this->document->update(['status' => DocumentStatus::FULL_GENERATING]);
                Log::channel('queue')->info('Статус документа изменен на full_generating', [
                    'document_id' => $this->document->id,
                    'previous_status' => $this->document->status->value
                ]);
            }

            // Проверяем, что документ имеет структуру для генерации
            $structure = $this->document->structure;
            if (!$structure || !isset($structure['contents']) || empty($structure['contents'])) {
                throw new \Exception('Нет структуры документа для полной генерации');
            }

            // Получаем настройки GPT из документа
            $gptSettings = $this->document->gpt_settings ?? [];
            $service = $gptSettings['service'] ?? 'openai';
            $temperature = $gptSettings['temperature'] ?? 0.8;

            // Получаем сервис из фабрики
            $gptService = $factory->make($service);

            Log::channel('queue')->info('Начинаем генерацию через ассистента', [
                'document_id' => $this->document->id,
                'service' => $service,
                'assistant_id' => 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju'
            ]);

            // ID ассистента для полной генерации
            $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';
            
            // Получаем структуру документа
            $contents = $structure['contents'] ?? [];
            
            if (empty($contents)) {
                throw new \Exception('Нет структуры документа для полной генерации');
            }

            // Подготавливаем результирующую структуру
            $generatedContent = [
                'topics' => []
            ];

            // Генерируем содержимое для каждого раздела
            foreach ($contents as $topicIndex => $topic) {
                Log::channel('queue')->info('Генерируем раздел', [
                    'document_id' => $this->document->id,
                    'topic_index' => $topicIndex,
                    'topic_title' => $topic['title']
                ]);

                $generatedTopic = [
                    'title' => $topic['title'],
                    'subtopics' => []
                ];

                // Генерируем каждый подраздел отдельно
                foreach ($topic['subtopics'] as $subtopicIndex => $subtopic) {
                    Log::channel('queue')->info('Генерируем подраздел', [
                        'document_id' => $this->document->id,
                        'topic_index' => $topicIndex,
                        'subtopic_index' => $subtopicIndex,
                        'subtopic_title' => $subtopic['title']
                    ]);

                    // Создаем thread для данного subtopic
                    $thread = $gptService->createThread();
                    
                    // Формируем промпт для конкретного subtopic
                    $prompt = $this->buildSubtopicPrompt($subtopic);
                    
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
                        throw new \Exception("Не получен ответ от ассистента для подраздела: {$subtopic['title']}");
                    }

                    // Парсим JSON ответ от ассистента
                    $parsedResponse = null;
                    try {
                        $parsedResponse = json_decode($assistantMessage, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('Ошибка парсинга JSON: ' . json_last_error_msg());
                        }
                    } catch (\Exception $e) {
                        Log::channel('queue')->warning('Не удалось распарсить JSON ответ, используем текст как есть', [
                            'document_id' => $this->document->id,
                            'subtopic_title' => $subtopic['title'],
                            'response' => $assistantMessage,
                            'error' => $e->getMessage()
                        ]);
                        // Если не удалось распарсить JSON, используем весь ответ как текст
                        $parsedResponse = ['text' => $assistantMessage];
                    }

                    // Извлекаем text из ответа или используем весь ответ если text нет
                    $contentText = $parsedResponse['text'] ?? $assistantMessage;

                    // Добавляем сгенерированный контент к подразделу
                    $generatedSubtopic = [
                        'title' => $subtopic['title'],
                        'content' => trim($contentText)
                    ];

                    $generatedTopic['subtopics'][] = $generatedSubtopic;

                    Log::channel('queue')->info('Подраздел сгенерирован', [
                        'document_id' => $this->document->id,
                        'subtopic_title' => $subtopic['title'],
                        'content_length' => strlen($contentText),
                        'was_json' => is_array($parsedResponse) && isset($parsedResponse['text'])
                    ]);

                    // Небольшая пауза между запросами
                    sleep(1);
                }

                $generatedContent['topics'][] = $generatedTopic;
            }

            // Сохраняем сгенерированный контент в поле content
            $this->document->update([
                'content' => $generatedContent,
                'status' => DocumentStatus::FULL_GENERATED
            ]);

            Log::channel('queue')->info('Документ полностью сгенерирован', [
                'document_id' => $this->document->id,
                'topics_count' => count($generatedContent['topics']),
                'total_subtopics' => array_sum(array_map(fn($topic) => count($topic['subtopics']), $generatedContent['topics']))
            ]);

            // ВРЕМЕННО ОТКЛЮЧЕНО: Создаем фиктивный GptRequest для совместимости с существующими событиями
            /*
            $gptRequest = new \App\Models\GptRequest([
                'document_id' => $this->document->id,
                'prompt' => 'Полная генерация по частям',
                'response' => 'Сгенерировано ' . count($generatedContent['topics']) . ' разделов',
                'status' => 'completed',
                'metadata' => [
                    'service' => $service,
                    'assistant_id' => $assistantId,
                    'generation_type' => 'full_by_parts',
                    'topics_count' => count($generatedContent['topics']),
                    'temperature' => $temperature,
                ]
            ]);
            $gptRequest->document = $this->document;

            event(new GptRequestCompleted($gptRequest));
            */

        } catch (\Exception $e) {
            Log::channel('queue')->error('Ошибка при полной генерации документа', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->document->update([
                'status' => DocumentStatus::FULL_GENERATION_FAILED
            ]);

            // ВРЕМЕННО ОТКЛЮЧЕНО: Создаем фиктивный GptRequest для события ошибки
            /*
            $gptRequest = new \App\Models\GptRequest([
                'document_id' => $this->document->id,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            $gptRequest->document = $this->document;

            event(new GptRequestFailed($gptRequest, $e->getMessage()));
            */

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('queue')->error('Job полной генерации документа завершился с ошибкой', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->document->update([
            'status' => DocumentStatus::FULL_GENERATION_FAILED
        ]);

        // ВРЕМЕННО ОТКЛЮЧЕНО: Создаем фиктивный GptRequest для события ошибки
        /*
        $gptRequest = new \App\Models\GptRequest([
            'document_id' => $this->document->id,
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
        $gptRequest->document = $this->document;

        event(new GptRequestFailed($gptRequest, $exception->getMessage()));
        */
    }

    /**
     * Формирует промпт для генерации конкретного подраздела
     */
    private function buildSubtopicPrompt(array $subtopic): string
    {
        // Формируем промпт только с description и полями subtopic, без лишнего текста
        $prompt = '';
        
        // Добавляем description если есть
        if (isset($subtopic['content']) && !empty($subtopic['content'])) {
            $prompt .= $subtopic['content'] . "\n\n";
        }
        
        // Добавляем все поля subtopic
        foreach ($subtopic as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $prompt .= ucfirst($key) . ": " . $value . "\n";
            } elseif (is_array($value) && !empty($value)) {
                $prompt .= ucfirst($key) . ": " . implode(', ', $value) . "\n";
            }
        }
        
        return trim($prompt);
    }
} 