<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\GptRequestCompleted;
use App\Events\GptRequestFailed;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Events\FullGenerationCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StartFullGenerateDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Время начала выполнения задачи
     */
    private float $startTime;

    /**
     * Счетчик шагов для детального логгирования
     */
    private int $stepCounter = 0;

    /**
     * Максимальное время выполнения задачи в секундах
     */
    public $timeout = 600;
    
    /**
     * Максимальное количество попыток
     */
    public $tries = 2;
    
    /**
     * Максимальное количество исключений
     */
    public $maxExceptions = 2;
    
    /**
     * Задержки между попытками (в секундах)
     */
    public $backoff = [120, 300]; // 2 минуты, 5 минут

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
        $this->startTime = microtime(true);
        $this->stepCounter = 0;
        
        try {
            // Инициализация детального логгирования
            $this->logDetailedStep(++$this->stepCounter, 'Начало полной генерации документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'job_id' => $this->job->getJobId(),
                'start_time' => date('Y-m-d H:i:s.v'),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'php_version' => PHP_VERSION,
                'process_id' => getmypid()
            ]);
            
            // Проверяем, не выполняется ли уже такая же задача для этого документа
            $activeJobsCount = DB::table('jobs')
                ->where('payload', 'like', '%"document_id":' . $this->document->id . '%')
                ->where('payload', 'like', '%StartFullGenerateDocument%')
                ->count();
                
            $this->logDetailedStep(++$this->stepCounter, 'Проверка дублирующих задач', [
                'document_id' => $this->document->id,
                'active_jobs_count' => $activeJobsCount,
                'current_job_id' => $this->job->getJobId()
            ]);
            
            if ($activeJobsCount > 1) {
                $this->logDetailedStep(++$this->stepCounter, 'ОБНАРУЖЕНО ДУБЛИРОВАНИЕ: Найдено несколько активных задач для этого документа', [
                    'document_id' => $this->document->id,
                    'active_jobs_count' => $activeJobsCount,
                    'current_job_id' => $this->job->getJobId(),
                    'action' => 'Прерываем выполнение этой задачи'
                ]);
                
                // Прерываем выполнение, чтобы избежать дублирования
                Log::channel('queue')->warning('Прервано выполнение дублирующей задачи полной генерации', [
                    'document_id' => $this->document->id,
                    'job_id' => $this->job->getJobId(),
                    'active_jobs_count' => $activeJobsCount
                ]);
                
                return;
            }
            
            // Безопасная перезагрузка документа - игнорируем ошибки подключения к БД
            try {
                $this->document->refresh();
                $this->logDetailedStep(++$this->stepCounter, 'Документ успешно обновлен из БД', [
                    'document_id' => $this->document->id,
                    'current_status' => $this->document->status->value
                ]);
            } catch (\Exception $e) {
                $this->logDetailedStep(++$this->stepCounter, 'ПРЕДУПРЕЖДЕНИЕ: Не удалось обновить документ из БД', [
                    'document_id' => $this->document->id,
                    'error' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]);
                // Продолжаем работу с текущими данными документа
            }
            
            $this->logDetailedStep(++$this->stepCounter, 'Логирование в стандартный канал queue', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'current_status' => $this->document->status->value,
                'job_id' => $this->job->getJobId()
            ]);
            
            Log::channel('queue')->info('Начало полной генерации документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'current_status' => $this->document->status->value,
                'job_id' => $this->job->getJobId()
            ]);

            // Устанавливаем статус "full_generating" если он еще не установлен
            if ($this->document->status !== DocumentStatus::FULL_GENERATING) {
                $this->logDetailedStep(++$this->stepCounter, 'Изменяем статус документа на full_generating', [
                    'previous_status' => $this->document->status->value,
                    'new_status' => DocumentStatus::FULL_GENERATING->value
                ]);
                
                $this->document->update(['status' => DocumentStatus::FULL_GENERATING]);
                
                Log::channel('queue')->info('Статус документа изменен на full_generating', [
                    'document_id' => $this->document->id,
                    'previous_status' => $this->document->status->value
                ]);
            } else {
                $this->logDetailedStep(++$this->stepCounter, 'Статус документа уже full_generating', [
                    'current_status' => $this->document->status->value
                ]);
            }

            // Проверяем, что документ имеет структуру для генерации
            $structure = $this->document->structure;
            $this->logDetailedStep(++$this->stepCounter, 'Проверяем структуру документа', [
                'has_structure' => !empty($structure),
                'has_contents' => !empty($structure['contents'] ?? []),
                'structure_keys' => array_keys($structure ?? []),
                'contents_count' => count($structure['contents'] ?? [])
            ]);
            
            if (!$structure || !isset($structure['contents']) || empty($structure['contents'])) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА: Нет структуры документа для полной генерации', [
                    'structure_empty' => empty($structure),
                    'contents_missing' => !isset($structure['contents']),
                    'contents_empty' => empty($structure['contents'] ?? []),
                    'structure_dump' => $structure
                ]);
                throw new \Exception('Нет структуры документа для полной генерации');
            }

            // Получаем настройки GPT из документа
            $gptSettings = $this->document->gpt_settings ?? [];
            $service = $gptSettings['service'] ?? 'openai';
            $temperature = $gptSettings['temperature'] ?? 0.8;
            
            $this->logDetailedStep(++$this->stepCounter, 'Получены настройки GPT', [
                'gpt_settings' => $gptSettings,
                'service' => $service,
                'temperature' => $temperature,
                'settings_source' => empty($this->document->gpt_settings) ? 'default' : 'document'
            ]);

            // Получаем сервис из фабрики
            $this->logDetailedStep(++$this->stepCounter, 'Создаем GPT сервис', [
                'service' => $service,
                'factory_class' => get_class($factory)
            ]);
            
            $gptService = $factory->make($service);
            
            $this->logDetailedStep(++$this->stepCounter, 'GPT сервис создан', [
                'service_class' => get_class($gptService),
                'service_name' => $gptService->getName()
            ]);

            $this->logDetailedStep(++$this->stepCounter, 'Начинаем генерацию через ассистента', [
                'document_id' => $this->document->id,
                'service' => $service,
                'assistant_id' => 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju'
            ]);
            
            Log::channel('queue')->info('Начинаем генерацию через ассистента', [
                'document_id' => $this->document->id,
                'service' => $service,
                'assistant_id' => 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju'
            ]);

            // ID ассистента для полной генерации
            $assistantId = 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju';
            
            // Получаем thread_id из документа
            $threadId = $this->document->thread_id;
            
            $this->logDetailedStep(++$this->stepCounter, 'Проверяем thread_id документа', [
                'thread_id' => $threadId,
                'has_thread_id' => !empty($threadId),
                'assistant_id' => $assistantId
            ]);
            
            if (!$threadId) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА: Не найден thread_id для документа', [
                    'document_id' => $this->document->id,
                    'thread_id' => $threadId,
                    'document_status' => $this->document->status->value
                ]);
                throw new \Exception('Не найден thread_id для документа. Сначала должна быть создана структура.');
            }
            
            $this->logDetailedStep(++$this->stepCounter, 'Используем существующий thread', [
                'document_id' => $this->document->id,
                'thread_id' => $threadId
            ]);
            
            Log::channel('queue')->info('Используем существующий thread', [
                'document_id' => $this->document->id,
                'thread_id' => $threadId
            ]);
            
            // Получаем структуру документа
            $contents = $structure['contents'] ?? [];
            
            $this->logDetailedStep(++$this->stepCounter, 'Получена структура содержимого', [
                'contents_count' => count($contents),
                'contents_keys' => array_keys($contents),
                'first_topic_title' => $contents[0]['title'] ?? 'не найден'
            ]);
            
            if (empty($contents)) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА: Пустая структура содержимого', [
                    'contents_empty' => empty($contents),
                    'structure_contents' => $structure['contents'] ?? 'отсутствует'
                ]);
                throw new \Exception('Нет структуры документа для полной генерации');
            }

            // Подготавливаем результирующую структуру
            $generatedContent = [
                'topics' => []
            ];

            $this->logDetailedStep(++$this->stepCounter, 'Начинаем генерацию содержимого по частям', [
                'total_topics' => count($contents),
                'assistant_id' => $assistantId,
                'thread_id' => $threadId
            ]);

            // Генерируем содержимое для каждого раздела
            foreach ($contents as $topicIndex => $topic) {
                $this->logDetailedStep(++$this->stepCounter, 'Начинаем генерацию топика', [
                    'topic_index' => $topicIndex,
                    'topic_title' => $topic['title'],
                    'subtopics_count' => count($topic['subtopics'] ?? [])
                ]);
                
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
                    $this->logDetailedStep(++$this->stepCounter, 'НАЧАЛО ГЕНЕРАЦИИ ПОДРАЗДЕЛА', [
                        'topic_index' => $topicIndex,
                        'subtopic_index' => $subtopicIndex,
                        'subtopic_title' => $subtopic['title'],
                        'thread_id' => $threadId,
                        'assistant_id' => $assistantId
                    ]);
                    
                    Log::channel('queue')->info('Генерируем подраздел', [
                        'document_id' => $this->document->id,
                        'topic_index' => $topicIndex,
                        'subtopic_index' => $subtopicIndex,
                        'subtopic_title' => $subtopic['title']
                    ]);

                    // Проверяем состояние thread перед добавлением сообщения
                    $this->checkThreadStateDetailed($gptService, $threadId, 'Перед добавлением сообщения');

                    // Используем существующий thread
                    $prompt = $this->buildSubtopicPrompt($subtopic);
                    
                    $this->logDetailedStep(++$this->stepCounter, 'Построен промпт для подраздела', [
                        'subtopic_title' => $subtopic['title'],
                        'prompt_length' => mb_strlen($prompt),
                        'prompt_preview' => mb_substr($prompt, 0, 200) . '...'
                    ]);
                    
                    // Безопасно добавляем сообщение в существующий thread с проверкой активных run
                    $this->logDetailedStep(++$this->stepCounter, 'Добавляем сообщение в thread', [
                        'thread_id' => $threadId,
                        'subtopic_title' => $subtopic['title'],
                        'message_length' => mb_strlen($prompt)
                    ]);
                    
                    try {
                        $gptService->safeAddMessageToThread($threadId, $prompt);
                        $this->logDetailedStep(++$this->stepCounter, 'Сообщение успешно добавлено в thread', [
                            'thread_id' => $threadId,
                            'subtopic_title' => $subtopic['title']
                        ]);
                    } catch (\Exception $e) {
                        $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при добавлении сообщения в thread', [
                            'thread_id' => $threadId,
                            'subtopic_title' => $subtopic['title'],
                            'error' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine(),
                            'error_trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                    
                    // Проверяем состояние thread после добавления сообщения
                    $this->checkThreadStateDetailed($gptService, $threadId, 'После добавления сообщения');
                    
                    // Запускаем run с ассистентом
                    $this->logDetailedStep(++$this->stepCounter, 'Создаем run с ассистентом', [
                        'thread_id' => $threadId,
                        'assistant_id' => $assistantId,
                        'subtopic_title' => $subtopic['title']
                    ]);
                    
                    try {
                        $run = $gptService->createRun($threadId, $assistantId);
                        $this->logDetailedStep(++$this->stepCounter, 'Run успешно создан', [
                            'thread_id' => $threadId,
                            'run_id' => $run['id'],
                            'status' => $run['status'],
                            'subtopic_title' => $subtopic['title']
                        ]);
                    } catch (\Exception $e) {
                        $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при создании run', [
                            'thread_id' => $threadId,
                            'assistant_id' => $assistantId,
                            'subtopic_title' => $subtopic['title'],
                            'error' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                    
                    // Проверяем состояние thread после создания run
                    $this->checkThreadStateDetailed($gptService, $threadId, 'После создания run');
                    
                    // Ждем завершения run
                    $this->logDetailedStep(++$this->stepCounter, 'Ожидаем завершения run', [
                        'thread_id' => $threadId,
                        'run_id' => $run['id'],
                        'subtopic_title' => $subtopic['title']
                    ]);
                    
                    try {
                        $completedRun = $gptService->waitForRunCompletion($threadId, $run['id']);
                        $this->logDetailedStep(++$this->stepCounter, 'Run успешно завершен', [
                            'thread_id' => $threadId,
                            'run_id' => $run['id'],
                            'final_status' => $completedRun['status'],
                            'usage' => $completedRun['usage'] ?? null,
                            'subtopic_title' => $subtopic['title']
                        ]);
                    } catch (\Exception $e) {
                        $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при ожидании завершения run', [
                            'thread_id' => $threadId,
                            'run_id' => $run['id'],
                            'subtopic_title' => $subtopic['title'],
                            'error' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                    
                    // Логируем информацию о завершении run с токенами для подраздела
                    $this->logDetailedStep(++$this->stepCounter, 'Run для подраздела завершен (стандартное логирование)', [
                        'thread_id' => $threadId,
                        'run_id' => $run['id'],
                        'subtopic_title' => $subtopic['title'],
                        'status' => $completedRun['status'],
                        'usage' => $completedRun['usage'] ?? null
                    ]);
                    
                    Log::channel('queue')->info('Run для подраздела завершен', [
                        'document_id' => $this->document->id,
                        'thread_id' => $threadId,
                        'run_id' => $run['id'],
                        'subtopic_title' => $subtopic['title'],
                        'status' => $completedRun['status'],
                        'usage' => $completedRun['usage'] ?? null
                    ]);
                    
                    // Получаем сообщения из thread
                    $this->logDetailedStep(++$this->stepCounter, 'Получаем сообщения из thread', [
                        'thread_id' => $threadId,
                        'subtopic_title' => $subtopic['title']
                    ]);
                    
                    try {
                        $messages = $gptService->getThreadMessages($threadId);
                        $this->logDetailedStep(++$this->stepCounter, 'Сообщения из thread получены', [
                            'thread_id' => $threadId,
                            'messages_count' => count($messages['data'] ?? []),
                            'subtopic_title' => $subtopic['title']
                        ]);
                    } catch (\Exception $e) {
                        $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при получении сообщений из thread', [
                            'thread_id' => $threadId,
                            'subtopic_title' => $subtopic['title'],
                            'error' => $e->getMessage(),
                            'error_file' => $e->getFile(),
                            'error_line' => $e->getLine()
                        ]);
                        throw $e;
                    }
                    
                    // Находим последнее сообщение ассистента
                    $assistantMessage = null;
                    $messagesAnalyzed = 0;
                    foreach ($messages['data'] as $message) {
                        $messagesAnalyzed++;
                        if ($message['role'] === 'assistant') {
                            $assistantMessage = $message['content'][0]['text']['value'];
                            break;
                        }
                    }
                    
                    $this->logDetailedStep(++$this->stepCounter, 'Поиск сообщения ассистента', [
                        'thread_id' => $threadId,
                        'subtopic_title' => $subtopic['title'],
                        'messages_analyzed' => $messagesAnalyzed,
                        'assistant_message_found' => !empty($assistantMessage),
                        'assistant_message_length' => mb_strlen($assistantMessage ?? ''),
                        'assistant_message_preview' => mb_substr($assistantMessage ?? '', 0, 200) . '...'
                    ]);
                    
                    if (!$assistantMessage) {
                        $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА: Не получен ответ от ассистента', [
                            'thread_id' => $threadId,
                            'subtopic_title' => $subtopic['title'],
                            'messages_count' => count($messages['data'] ?? []),
                            'messages_roles' => array_column($messages['data'] ?? [], 'role')
                        ]);
                        throw new \Exception('Не получен ответ от ассистента для подраздела: ' . $subtopic['title']);
                    }
                    
                    // Парсим JSON ответ если он есть, иначе используем как обычный текст
                    $contentText = $assistantMessage;
                    $isJsonResponse = false;
                    if (strpos($assistantMessage, '{') !== false) {
                        $jsonData = json_decode($assistantMessage, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['text'])) {
                            $contentText = $jsonData['text'];
                            $isJsonResponse = true;
                        }
                    }
                    
                    $this->logDetailedStep(++$this->stepCounter, 'Ответ ассистента обработан', [
                        'thread_id' => $threadId,
                        'subtopic_title' => $subtopic['title'],
                        'original_length' => mb_strlen($assistantMessage),
                        'processed_length' => mb_strlen($contentText),
                        'is_json_response' => $isJsonResponse,
                        'json_parse_error' => $isJsonResponse ? null : json_last_error_msg()
                    ]);
                    
                    // Добавляем сгенерированный подраздел в topic
                    $subtopicData = [
                        'title' => $subtopic['title'],
                        'content' => $contentText, // Используем обычный текст, а не JSON
                        'generated_at' => now()->toDateTimeString(),
                        'run_id' => $run['id'],
                        'usage' => $completedRun['usage'] ?? null
                    ];
                    
                    $generatedTopic['subtopics'][] = $subtopicData;
                    
                    $this->logDetailedStep(++$this->stepCounter, 'Подраздел добавлен в результат', [
                        'subtopic_title' => $subtopic['title'],
                        'content_length' => mb_strlen($contentText),
                        'usage' => $completedRun['usage'] ?? null,
                        'subtopics_in_topic' => count($generatedTopic['subtopics'])
                    ]);

                    Log::channel('queue')->info('Подраздел успешно сгенерирован', [
                        'document_id' => $this->document->id,
                        'subtopic_title' => $subtopic['title'],
                        'content_length' => mb_strlen($contentText),
                        'usage' => $completedRun['usage'] ?? null
                    ]);

                    // Увеличенная пауза между запросами для стабилизации thread
                    $this->logDetailedStep(++$this->stepCounter, 'Пауза между запросами', [
                        'seconds' => 2,
                        'subtopic_title' => $subtopic['title'],
                        'reason' => 'стабилизация thread'
                    ]);
                    sleep(2);
                }

                // Добавляем готовый topic в результат (только один раз!)
                $generatedContent['topics'][] = $generatedTopic;
                
                $this->logDetailedStep(++$this->stepCounter, 'Топик завершен и добавлен в результат', [
                    'topic_title' => $topic['title'],
                    'subtopics_generated' => count($generatedTopic['subtopics']),
                    'total_topics_completed' => count($generatedContent['topics'])
                ]);
            }

            // Сохраняем сгенерированный контент в поле content
            $this->logDetailedStep(++$this->stepCounter, 'Готовимся сохранить сгенерированный контент', [
                'topics_count' => count($generatedContent['topics']),
                'total_subtopics' => array_sum(array_map(function($topic) {
                    return count($topic['subtopics'] ?? []);
                }, $generatedContent['topics'])),
                'content_structure_keys' => array_keys($generatedContent),
                'content_size_bytes' => mb_strlen(json_encode($generatedContent, JSON_UNESCAPED_UNICODE))
            ]);
            
            Log::channel('queue')->info('ОТЛАДКА: Готовимся сохранить content', [
                'document_id' => $this->document->id,
                'generated_content_structure' => [
                    'topics_count' => count($generatedContent['topics']),
                    'content_preview' => json_encode($generatedContent, JSON_UNESCAPED_UNICODE)
                ]
            ]);
            
            try {
                $this->document->update([
                    'content' => $generatedContent,
                    'status' => DocumentStatus::FULL_GENERATED
                ]);
                
                $this->logDetailedStep(++$this->stepCounter, 'Контент успешно сохранен в БД', [
                    'document_id' => $this->document->id,
                    'new_status' => DocumentStatus::FULL_GENERATED->value
                ]);
                
            } catch (\Exception $e) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при сохранении контента в БД', [
                    'document_id' => $this->document->id,
                    'error' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]);
                throw $e;
            }

            // Проверяем сохранение
            try {
                $freshDocument = $this->document->fresh();
                $savedContentCheck = empty($freshDocument->content) ? 'ПУСТОЙ' : 'ЕСТЬ_ДАННЫЕ';
                
                $this->logDetailedStep(++$this->stepCounter, 'Проверка сохранения контента', [
                    'document_id' => $this->document->id,
                    'saved_content_check' => $savedContentCheck,
                    'fresh_document_status' => $freshDocument->status->value,
                    'content_topics_count' => count($freshDocument->content['topics'] ?? [])
                ]);
                
                Log::channel('queue')->info('ОТЛАДКА: Content сохранен', [
                    'document_id' => $this->document->id,
                    'saved_content_check' => $savedContentCheck
                ]);
                
            } catch (\Exception $e) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при проверке сохранения контента', [
                    'document_id' => $this->document->id,
                    'error' => $e->getMessage()
                ]);
            }

            // После успешной генерации вызываем событие
            $this->logDetailedStep(++$this->stepCounter, 'Вызываем событие FullGenerationCompleted', [
                'document_id' => $this->document->id,
                'event_class' => FullGenerationCompleted::class
            ]);
            
            event(new FullGenerationCompleted($this->document));

            $executionTime = round(microtime(true) - $this->startTime, 3);
            
            $this->logDetailedStep(++$this->stepCounter, 'ПОЛНАЯ ГЕНЕРАЦИЯ ЗАВЕРШЕНА УСПЕШНО', [
                'document_id' => $this->document->id,
                'total_execution_time' => $executionTime . 's',
                'total_steps' => $this->stepCounter,
                'topics_generated' => count($generatedContent['topics']),
                'subtopics_generated' => array_sum(array_map(function($topic) {
                    return count($topic['subtopics'] ?? []);
                }, $generatedContent['topics'])),
                'memory_peak' => memory_get_peak_usage(true),
                'memory_current' => memory_get_usage(true)
            ]);
            
            Log::info('Полная генерация документа завершена', [
                'document_id' => $this->document->id
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
            $executionTime = round(microtime(true) - $this->startTime, 3);
            
            $this->logDetailedStep(++$this->stepCounter, 'КРИТИЧЕСКАЯ ОШИБКА при полной генерации документа', [
                'document_id' => $this->document->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_class' => get_class($e),
                'execution_time_before_error' => $executionTime . 's',
                'steps_completed' => $this->stepCounter - 1,
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            Log::channel('queue')->error('Ошибка при полной генерации документа', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            try {
                $this->document->update([
                    'status' => DocumentStatus::FULL_GENERATION_FAILED
                ]);
                
                $this->logDetailedStep(++$this->stepCounter, 'Статус документа изменен на FULL_GENERATION_FAILED', [
                    'document_id' => $this->document->id,
                    'new_status' => DocumentStatus::FULL_GENERATION_FAILED->value
                ]);
                
            } catch (\Exception $updateException) {
                $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при изменении статуса документа на FAILED', [
                    'document_id' => $this->document->id,
                    'update_error' => $updateException->getMessage(),
                    'original_error' => $e->getMessage()
                ]);
            }

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
        $executionTime = isset($this->startTime) ? round(microtime(true) - $this->startTime, 3) : 0;
        
        $this->logDetailedStep(++$this->stepCounter, 'JOB FAILED - Вызван метод failed()', [
            'document_id' => $this->document->id,
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_class' => get_class($exception),
            'total_execution_time' => $executionTime . 's',
            'steps_completed' => $this->stepCounter - 1,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'exception_trace' => $exception->getTraceAsString()
        ]);
        
        Log::channel('queue')->error('Job полной генерации документа завершился с ошибкой', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        try {
            $this->document->update([
                'status' => DocumentStatus::FULL_GENERATION_FAILED
            ]);
            
            $this->logDetailedStep(++$this->stepCounter, 'Статус документа изменен на FAILED в методе failed()', [
                'document_id' => $this->document->id,
                'new_status' => DocumentStatus::FULL_GENERATION_FAILED->value
            ]);
            
        } catch (\Exception $updateException) {
            $this->logDetailedStep(++$this->stepCounter, 'ОШИБКА при изменении статуса в методе failed()', [
                'document_id' => $this->document->id,
                'update_error' => $updateException->getMessage(),
                'original_exception' => $exception->getMessage()
            ]);
        }

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

    /**
     * Детальное логгирование с максимальной информацией
     */
    private function logDetailedStep(int $step, string $message, array $data = []): void
    {
        $timeFromStart = isset($this->startTime) ? round(microtime(true) - $this->startTime, 3) : 0;
        
        $logData = array_merge([
            'step' => $step,
            'timestamp' => date('Y-m-d H:i:s.v'),
            'time_from_start' => $timeFromStart . 's',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'document_id' => $this->document->id
        ], $data);

        // Логируем в специальный канал для полной генерации
        Log::channel('full_generation')->info("ШАГ {$step}: {$message}", $logData);
        
        // Дублируем в стандартный канал queue для совместимости
        Log::channel('queue')->info("ШАГ {$step}: {$message}", $logData);
    }

    /**
     * Детальная проверка состояния thread
     */
    private function checkThreadStateDetailed($gptService, string $threadId, string $context): void
    {
        $this->logDetailedStep(++$this->stepCounter, "Проверка состояния thread: {$context}", [
            'thread_id' => $threadId,
            'context' => $context
        ]);

        try {
            // Проверяем активные run через метод hasActiveRuns
            $hasActiveViaMethod = $gptService->hasActiveRuns($threadId);
            
            // Получаем все run вручную для детального анализа
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs");

            if ($response->successful()) {
                $runs = $response->json();
                
                $activeRuns = [];
                $completedRuns = [];
                $failedRuns = [];
                $otherRuns = [];
                
                foreach ($runs['data'] ?? [] as $run) {
                    if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                        $activeRuns[] = [
                            'id' => $run['id'],
                            'status' => $run['status'],
                            'created_at' => $run['created_at'] ?? null,
                            'started_at' => $run['started_at'] ?? null
                        ];
                    } elseif ($run['status'] === 'completed') {
                        $completedRuns[] = [
                            'id' => $run['id'],
                            'status' => $run['status'],
                            'completed_at' => $run['completed_at'] ?? null
                        ];
                    } elseif (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                        $failedRuns[] = [
                            'id' => $run['id'],
                            'status' => $run['status'],
                            'last_error' => $run['last_error'] ?? null
                        ];
                    } else {
                        $otherRuns[] = [
                            'id' => $run['id'],
                            'status' => $run['status']
                        ];
                    }
                }
                
                $manualCheckHasActive = count($activeRuns) > 0;
                
                $this->logDetailedStep(++$this->stepCounter, "Детальный анализ thread: {$context}", [
                    'thread_id' => $threadId,
                    'context' => $context,
                    'total_runs' => count($runs['data'] ?? []),
                    'active_runs' => count($activeRuns),
                    'completed_runs' => count($completedRuns),
                    'failed_runs' => count($failedRuns),
                    'other_runs' => count($otherRuns),
                    'has_active_via_method' => $hasActiveViaMethod,
                    'manual_check_has_active' => $manualCheckHasActive,
                    'method_matches_manual' => $hasActiveViaMethod === $manualCheckHasActive,
                    'active_runs_details' => $activeRuns,
                    'recent_completed_runs' => array_slice($completedRuns, 0, 3),
                    'recent_failed_runs' => array_slice($failedRuns, 0, 3)
                ]);
                
                // Дополнительная проверка: если есть активные run, логируем их детально
                if (count($activeRuns) > 0) {
                    foreach ($activeRuns as $activeRun) {
                        $this->logDetailedStep(++$this->stepCounter, "АКТИВНЫЙ RUN ОБНАРУЖЕН", [
                            'thread_id' => $threadId,
                            'run_id' => $activeRun['id'],
                            'status' => $activeRun['status'],
                            'created_at' => $activeRun['created_at'],
                            'started_at' => $activeRun['started_at'],
                            'context' => $context
                        ]);
                    }
                }
                
            } else {
                $this->logDetailedStep(++$this->stepCounter, "Ошибка при получении run для thread", [
                    'thread_id' => $threadId,
                    'context' => $context,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logDetailedStep(++$this->stepCounter, "Исключение при проверке thread", [
                'thread_id' => $threadId,
                'context' => $context,
                'error' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
        }
    }
} 