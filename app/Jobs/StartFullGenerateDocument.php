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
            Log::channel('queue')->info('Начало полной генерации документа', [
                'document_id' => $this->document->id,
                'document_title' => $this->document->title,
                'job_id' => $this->job->getJobId()
            ]);

            // Проверяем, что документ готов к полной генерации
            if (!$this->document->status->canStartFullGeneration()) {
                throw new \Exception('Документ не готов к полной генерации. Текущий статус: ' . $this->document->status->value);
            }

            // Обновляем статус документа на "full_generating"
            $this->document->update(['status' => DocumentStatus::FULL_GENERATING]);

            // Получаем настройки GPT из документа
            $gptSettings = $this->document->gpt_settings ?? [];
            $service = $gptSettings['service'] ?? 'openai';
            $model = $gptSettings['model'] ?? 'gpt-4'; // Используем более мощную модель для полной генерации
            $temperature = $gptSettings['temperature'] ?? 0.8; // Немного больше креативности

            // Получаем сервис из фабрики
            $gptService = $factory->make($service);

            // Формируем промпт для полной генерации документа
            $prompt = $this->buildFullPrompt();

            Log::channel('queue')->info('Отправляем запрос на полную генерацию к GPT сервису', [
                'document_id' => $this->document->id,
                'service' => $service,
                'model' => $model
            ]);

            // Отправляем запрос к GPT сервису
            $response = $gptService->sendRequest($prompt, [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            // Парсим ответ и извлекаем детальное содержимое
            $parsedData = $this->parseFullGptResponse($response['content']);

            // Обновляем структуру документа с детальным содержимым
            $structure = $this->document->structure ?? [];
            $structure['detailed_contents'] = $parsedData['detailed_contents'] ?? [];
            $structure['introduction'] = $parsedData['introduction'] ?? '';
            $structure['conclusion'] = $parsedData['conclusion'] ?? '';
            $structure['detailed_objectives'] = $parsedData['detailed_objectives'] ?? [];

            // Сохраняем изменения
            $this->document->update([
                'structure' => $structure,
                'status' => DocumentStatus::FULL_GENERATED
            ]);

            Log::channel('queue')->info('Документ полностью сгенерирован', [
                'document_id' => $this->document->id,
                'detailed_contents_count' => count($structure['detailed_contents']),
                'introduction_length' => strlen($structure['introduction']),
                'conclusion_length' => strlen($structure['conclusion']),
                'tokens_used' => $response['tokens_used'] ?? 0
            ]);

            // Создаем фиктивный GptRequest для совместимости с существующими событиями
            $gptRequest = new \App\Models\GptRequest([
                'document_id' => $this->document->id,
                'prompt' => $prompt,
                'response' => $response['content'],
                'status' => 'completed',
                'metadata' => [
                    'service' => $service,
                    'model' => $response['model'] ?? $model,
                    'tokens_used' => $response['tokens_used'] ?? 0,
                    'temperature' => $temperature,
                    'generation_type' => 'full'
                ]
            ]);
            $gptRequest->document = $this->document;

            event(new GptRequestCompleted($gptRequest));

        } catch (\Exception $e) {
            Log::channel('queue')->error('Ошибка при полной генерации документа', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->document->update([
                'status' => DocumentStatus::FULL_GENERATION_FAILED
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
        Log::channel('queue')->error('Job полной генерации документа завершился с ошибкой', [
            'document_id' => $this->document->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->document->update([
            'status' => DocumentStatus::FULL_GENERATION_FAILED
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
     * Формирует промпт для полной генерации документа
     */
    private function buildFullPrompt(): string
    {
        $structure = $this->document->structure;
        $topic = $structure['topic'] ?? $this->document->title;
        $documentType = $this->document->documentType->name ?? 'документ';
        $objectives = $structure['objectives'] ?? [];
        $contents = $structure['contents'] ?? [];

        $objectivesText = implode("\n", array_map(fn($obj, $i) => ($i + 1) . ". $obj", $objectives, array_keys($objectives)));
        
        $contentsText = '';
        foreach ($contents as $i => $content) {
            $contentsText .= ($i + 1) . ". " . $content['title'] . "\n";
            foreach ($content['subtopics'] as $j => $subtopic) {
                $contentsText .= "   " . ($j + 1) . ". " . $subtopic['title'] . "\n";
            }
        }

        return "
        Создай детальное содержимое для документа типа '{$documentType}' на тему: '{$topic}'.
        
        БАЗОВАЯ СТРУКТУРА ДОКУМЕНТА:
        
        Цели:
        {$objectivesText}
        
        Содержание:
        {$contentsText}
        
        ЗАДАЧА: Создай полное детальное содержимое документа в формате JSON:
        
        {
            \"introduction\": \"Подробное введение к документу (минимум 500 слов)\",
            \"detailed_objectives\": [
                {
                    \"title\": \"Название цели\",
                    \"description\": \"Подробное описание цели\",
                    \"success_criteria\": \"Критерии успеха\"
                }
            ],
            \"detailed_contents\": [
                {
                    \"title\": \"Название раздела\",
                    \"introduction\": \"Введение к разделу\",
                    \"subtopics\": [
                        {
                            \"title\": \"Название подраздела\",
                            \"content\": \"Детальное содержание подраздела (минимум 300 слов)\",
                            \"examples\": [\"Пример 1\", \"Пример 2\"],
                            \"key_points\": [\"Ключевой момент 1\", \"Ключевой момент 2\"]
                        }
                    ],
                    \"summary\": \"Краткое резюме раздела\"
                }
            ],
            \"conclusion\": \"Подробное заключение документа (минимум 400 слов)\"
        }
        
        ТРЕБОВАНИЯ:
        - Все тексты должны быть содержательными и профессиональными
        - Каждый подраздел должен содержать минимум 300 слов
        - Включи практические примеры и ключевые моменты
        - Введение и заключение должны быть подробными
        - Соблюдай научный/деловой стиль изложения
        ";
    }

    /**
     * Парсит ответ от GPT и извлекает детальные структурированные данные
     */
    private function parseFullGptResponse(string $response): array
    {
        // Пытаемся найти JSON в ответе
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('Не удалось найти JSON в ответе GPT при полной генерации');
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Ошибка парсинга JSON при полной генерации: ' . json_last_error_msg());
        }
        
        // Валидируем структуру данных
        if (!isset($data['detailed_contents']) || !isset($data['introduction']) || !isset($data['conclusion'])) {
            throw new \Exception('Неверная структура данных в ответе GPT при полной генерации');
        }
        
        return $data;
    }
} 