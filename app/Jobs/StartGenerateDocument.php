<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Events\GptRequestCompleted;
use App\Events\GptRequestFailed;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\GptServiceInterface;
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
     * Максимальное время выполнения задачи в секундах
     */
    public $timeout = 600;

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
            
            // Создаем thread для контекста генерации
            $thread = $gptService->createThread();
            
            // Сохраняем thread_id в БД
            $this->document->update(['thread_id' => $thread['id']]);
            
            Log::channel('queue')->info('Создан thread для документа', [
                'document_id' => $this->document->id,
                'thread_id' => $thread['id']
            ]);
            
            // Добавляем сообщение в thread
            $gptService->addMessageToThread($thread['id'], $prompt);
            
            // Запускаем run с ассистентом
            $run = $gptService->createRun($thread['id'], $assistantId);
            
            // Ждем завершения run
            $completedRun = $gptService->waitForRunCompletion($thread['id'], $run['id']);
            
            // Логируем информацию о завершении run с токенами
            Log::channel('queue')->info('Run завершен', [
                'document_id' => $this->document->id,
                'thread_id' => $thread['id'],
                'run_id' => $run['id'],
                'status' => $run['status'],
                'usage' => $run['usage'] ?? null
            ]);

            // Получаем сообщения из thread
            $messages = $gptService->getThreadMessages($thread['id']);
            
            // Находим ответ ассистента (первое сообщение от assistant)
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

            // Парсим ответ
            $response = $this->parseGptResponse($assistantMessage);
            $parsedData = $response;

            // Формируем структуру документа
            $structure = [
                'contents' => $parsedData['contents'] ?? [],
                'objectives' => $parsedData['objectives'] ?? [],
                'generated_at' => now()->toDateTimeString(),
                'service' => $service,
                'assistant_id' => $assistantId,
                'thread_id' => $thread['id'],
                'run_id' => $run['id']
            ];
            
            if (isset($parsedData['document_title'])) {
                $structure['document_title'] = $parsedData['document_title'];
            }
            
            if (isset($parsedData['title'])) {
                $structure['title'] = $parsedData['title'];
            }
            
            if (isset($parsedData['description'])) {
                $structure['description'] = $parsedData['description'];
            }

            // Сохраняем изменения
            $this->document->update([
                'structure' => $structure,
                'status' => DocumentStatus::PRE_GENERATED
            ]);

            Log::channel('queue')->info('Документ успешно сгенерирован', [
                'document_id' => $this->document->id,
                'contents_count' => count($structure['contents']),
                'objectives_count' => count($structure['objectives']),
                'usage' => $run['usage'] ?? null
            ]);

            // Генерируем ссылки сразу после создания структуры
            $this->generateReferences($gptService);

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
        
        // Вычисляем количество страниц для содержания (минус титульник, содержание и список литературы)
        if (is_numeric($pagesNum) && $pagesNum > 3) {
            $contentPagesNum = $pagesNum - 3;
        } else {
            $contentPagesNum = $pagesNum; // Если не число или меньше 3, передаем как есть
        }

        return "
        {$documentType}, {$topic}, {$contentPagesNum}
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
        // Теперь проверяем наличие любых полезных данных, а не только contents и objectives
        if (empty($data) || !is_array($data)) {
            throw new \Exception('Пустой или неверный JSON в ответе GPT');
        }
        
        // Проверяем что есть хотя бы одно из ожидаемых полей
        $expectedFields = ['title', 'document_title', 'description', 'contents', 'objectives'];
        $hasAnyField = false;
        
        foreach ($expectedFields as $field) {
            if (isset($data[$field])) {
                $hasAnyField = true;
                break;
            }
        }
        
        if (!$hasAnyField) {
            throw new \Exception('В ответе GPT не найдено ни одного ожидаемого поля: ' . implode(', ', $expectedFields));
        }
        
        return $data;
    }

    private function generateReferences(GptServiceInterface $gptService): void
    {
        try {
            Log::channel('queue')->info('Начало генерации ссылок для документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
            ]);

            // Формируем промпт для поиска ссылок
            $prompt = $this->buildReferencesPrompt();

            Log::channel('queue')->info('Отправляем запрос для поиска ссылок', [
                'document_id' => $this->document->id
            ]);

            // Работаем с OpenAI chat completion с web search tools
            $messages = [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            // JSON схема для структурированного ответа
            $schema = [
                'type' => 'object',
                'properties' => [
                    'references' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => [
                                    'type' => 'string',
                                    'description' => 'Название ресурса'
                                ],
                                'url' => [
                                    'type' => 'string',
                                    'description' => 'URL ссылки на ресурс'
                                ],
                                'type' => [
                                    'type' => 'string',
                                    'enum' => ['article', 'pdf', 'book', 'website', 'research_paper', 'other'],
                                    'description' => 'Тип ресурса'
                                ],
                                'description' => [
                                    'type' => 'string',
                                    'description' => 'Краткое описание релевантности ресурса'
                                ],
                                'author' => [
                                    'type' => 'string',
                                    'description' => 'Автор ресурса (если известен)'
                                ],
                                'publication_date' => [
                                    'type' => 'string',
                                    'description' => 'Дата публикации (если известна)'
                                ]
                            ],
                            'required' => ['title', 'url', 'type', 'description']
                        ],
                        'minItems' => 5,
                        'maxItems' => 10
                    ]
                ],
                'required' => ['references']
            ];

            // Отправляем запрос с web search tools и JSON схемой
            $response = $gptService->generateWithWebSearch($messages, [
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'references_response',
                        'schema' => $schema
                    ]
                ]
            ]);

            if (!$response || !isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Не получен ответ от GPT сервиса для генерации ссылок');
            }

            $content = $response['choices'][0]['message']['content'];
            $referencesData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Ошибка парсинга JSON ответа ссылок: ' . json_last_error_msg());
            }

            if (!isset($referencesData['references'])) {
                throw new \Exception('Не найден массив ссылок в ответе');
            }

            // Валидируем и фильтруем ссылки
            $validReferences = $this->validateReferences($referencesData['references']);

            // Обновляем структуру документа
            $structure = $this->document->structure ?? [];
            $structure['references'] = $validReferences;

            // Сохраняем изменения
            $this->document->update([
                'structure' => $structure
            ]);

            Log::channel('queue')->info('Ссылки успешно сгенерированы', [
                'document_id' => $this->document->id,
                'references_count' => count($validReferences),
                'tokens_used' => $response['usage']['total_tokens'] ?? 0
            ]);

        } catch (\Exception $e) {
            Log::channel('queue')->warning('Ошибка при генерации ссылок (не критично)', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage()
            ]);
            // Не бросаем исключение, чтобы не сломать основную генерацию
        }
    }

    /**
     * Формирует промпт для поиска релевантных ссылок
     */
    private function buildReferencesPrompt(): string
    {
        $topic = $this->document->structure['topic'] ?? $this->document->title;
        $documentType = $this->document->documentType->name ?? 'документ';
        
        // Получаем содержание документа для более точного поиска
        $contents = $this->document->structure['contents'] ?? [];
        $subtopics = [];
        
        foreach ($contents as $content) {
            if (isset($content['subtopics'])) {
                foreach ($content['subtopics'] as $subtopic) {
                    $subtopics[] = $subtopic['title'] ?? '';
                }
            }
        }
        
        $subtopicsText = !empty($subtopics) ? implode(', ', array_slice($subtopics, 0, 5)) : '';

        return "ОБЯЗАТЕЛЬНО используй веб-поиск для поиска актуальных источников в интернете по теме: \"{$topic}\" (тип работы: {$documentType}).

" . (!empty($subtopicsText) ? "Основные подтемы: {$subtopicsText}.\n\n" : "") . 

"ТРЕБУЕТСЯ веб-поиск актуальной информации в интернете! Найди и проанализируй реальные источники:

1. Ищи статьи в Google Scholar, ResearchGate, Academia.edu
2. Ищи книги на сайтах издательств и библиотек
3. Ищи документы в научных базах данных
4. Ищи официальные сайты организаций по теме
5. Ищи образовательные ресурсы университетов

КРИТИЧНО: Все ссылки должны быть реальными, действующими URL-адресами, найденными через веб-поиск.

Для каждого найденного ресурса проверь актуальность и укажи:
- title: Точное название ресурса (как указано на сайте)
- url: Реальный действующий URL-адрес
- type: Тип ресурса (article, book, website, research_paper, pdf, other)
- description: Почему этот ресурс полезен для данной темы
- author: Автор (если указан на сайте)
- publication_date: Дата публикации (если найдена)

Обязательно найди минимум 8-10 различных актуальных источников через веб-поиск.

Верни результат строго в JSON формате согласно схеме.";
    }

    /**
     * Валидирует и фильтрует массив ссылок
     */
    private function validateReferences(array $references): array
    {
        $validReferences = [];
        
        foreach ($references as $reference) {
            // Проверяем обязательные поля
            if (!isset($reference['title']) || !isset($reference['url']) || 
                !isset($reference['type']) || !isset($reference['description'])) {
                continue;
            }
            
            // Проверяем, что URL выглядит валидно
            if (!filter_var($reference['url'], FILTER_VALIDATE_URL)) {
                continue;
            }
            
            // Проверяем тип ресурса
            $allowedTypes = ['article', 'pdf', 'book', 'website', 'research_paper', 'other'];
            if (!in_array($reference['type'], $allowedTypes)) {
                $reference['type'] = 'other';
            }
            
            $validReferences[] = [
                'title' => trim($reference['title']),
                'url' => trim($reference['url']),
                'type' => $reference['type'],
                'description' => trim($reference['description']),
                'author' => isset($reference['author']) ? trim($reference['author']) : null,
                'publication_date' => isset($reference['publication_date']) ? trim($reference['publication_date']) : null,
            ];
        }
        
        return $validReferences;
    }
} 