<?php

namespace App\Services\Gpt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService implements GptServiceInterface
{
    protected string $apiKey;
    protected ?string $organization;
    protected string $defaultModel;
    protected ?string $proxyUrl;
    protected bool $useProxy;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->organization = config('services.openai.organization');
        $this->defaultModel = config('services.openai.default_model', 'gpt-3.5-turbo');
        $this->proxyUrl = config('services.openai.proxy_url');
        $this->useProxy = config('services.openai.use_proxy', true);
    }

    /**
     * Создает HTTP клиент с настройками прокси и заголовками
     */
    private function getHttpClient(array $headers = []): \Illuminate\Http\Client\PendingRequest
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        $httpClient = Http::withHeaders(array_merge($defaultHeaders, $headers));
        
        $options = [
            'timeout' => 120,
            'connect_timeout' => 30,
        ];
        
        // Добавляем прокси только если включен
        if ($this->useProxy && !empty($this->proxyUrl)) {
            $options['proxy'] = $this->proxyUrl;
            Log::info('OpenAI request with proxy', ['proxy' => $this->proxyUrl]);
        } else {
            Log::info('OpenAI request without proxy');
        }

        return $httpClient->withOptions($options);
    }

    public function sendRequest(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;

        $response = $this->getHttpClient()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => $temperature,
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $result = $response->json();
        
        // Логируем использование токенов
        if (isset($result['usage'])) {
            Log::info('OpenAI Chat Completions API - токены использованы', [
                'model' => $model,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
                'total_tokens' => $result['usage']['total_tokens'] ?? 0,
                'prompt_token_details' => $result['usage']['prompt_token_details'] ?? [],
                'completion_token_details' => $result['usage']['completion_token_details'] ?? []
            ]);
        }
        
        return [
            'content' => $result['choices'][0]['message']['content'],
            'tokens_used' => $result['usage']['total_tokens'],
            'model' => $model,
        ];
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function getAvailableModels(): array
    {
        return [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo',
        ];
    }

    public function createThread(): array
    {
        $response = $this->getHttpClient([
            'OpenAI-Beta' => 'assistants=v2',
        ])->post('https://api.openai.com/v1/threads');

        if (!$response->successful()) {
            Log::error('OpenAI Create Thread failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI Create Thread failed: ' . $response->body());
        }

        return $response->json();
    }

    public function addMessageToThread(string $threadId, string $content): array
    {
        $response = $this->getHttpClient([
            'OpenAI-Beta' => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
            'role' => 'user',
            'content' => $content,
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Add Message failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI Add Message failed: ' . $response->body());
        }

        return $response->json();
    }

    public function createRun(string $threadId, string $assistantId): array
    {
        $response = $this->getHttpClient([
            'OpenAI-Beta' => 'assistants=v2',
        ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
            'assistant_id' => $assistantId,
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Create Run failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI Create Run failed: ' . $response->body());
        }

        return $response->json();
    }

    public function waitForRunCompletion(string $threadId, string $runId): array
    {
        $maxAttempts = 48; // Максимум 48 попыток (4 минуты при задержке 5 секунд)
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $run = $this->getRunStatus($threadId, $runId);
            
            if ($run['status'] === 'completed') {
                // Логируем информацию о токенах при завершении run
                if (isset($run['usage'])) {
                    Log::info('OpenAI Assistant API - токены использованы', [
                        'thread_id' => $threadId,
                        'run_id' => $runId,
                        'model' => $run['model'] ?? 'unknown',
                        'completion_tokens' => $run['usage']['completion_tokens'] ?? 0,
                        'prompt_tokens' => $run['usage']['prompt_tokens'] ?? 0,
                        'total_tokens' => $run['usage']['total_tokens'] ?? 0,
                        'prompt_token_details' => $run['usage']['prompt_token_details'] ?? [],
                        'completion_token_details' => $run['usage']['completion_token_details'] ?? []
                    ]);
                }
                
                // Дополнительная пауза после завершения run для стабилизации thread
                Log::info('Run завершен, ожидаем стабилизации thread', [
                    'thread_id' => $threadId,
                    'run_id' => $runId
                ]);
                sleep(2);
                
                return $run;
            }
            
            if (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                throw new \Exception('Run failed with status: ' . $run['status']);
            }

            sleep(5); // Ждем 5 секунд перед следующей проверкой
            $attempts++;
        }

        throw new \Exception('Run timeout: не удалось дождаться завершения за 4 минуты (возможно OpenAI перегружен)');
    }

    /**
     * Получить статус run
     */
    public function getRunStatus(string $threadId, string $runId): array
    {
        $response = $this->getHttpClient([
            'OpenAI-Beta' => 'assistants=v2',
        ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

        if (!$response->successful()) {
            Log::error('OpenAI Get Run Status failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI Get Run Status failed: ' . $response->body());
        }

        return $response->json();
    }

    public function getThreadMessages(string $threadId): array
    {
        $response = $this->getHttpClient([
            'OpenAI-Beta' => 'assistants=v2',
        ])->get("https://api.openai.com/v1/threads/{$threadId}/messages");

        if (!$response->successful()) {
            Log::error('OpenAI Get Messages failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('OpenAI Get Messages failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Генерация с веб-поиском
     *
     * @param array $messages
     * @param array $options
     * @return array
     */
    public function generateWithWebSearch(array $messages, array $options = []): array
    {
        // Для web search используем специальную модель
        $model = 'gpt-4o-search-preview';
        
        $requestData = [
            'model' => $model,
            'messages' => $messages,
            // Используем web_search_options вместо tools для Chat Completions API
            'web_search_options' => (object)[]
        ];

        // Добавляем response_format если указан
        if (isset($options['response_format'])) {
            $requestData['response_format'] = $options['response_format'];
        }

        Log::info('OpenAI web search request', [
            'model' => $model,
            'has_web_search' => true,
            'messages_count' => count($messages)
        ]);

        $response = $this->getHttpClient()
            ->post('https://api.openai.com/v1/chat/completions', $requestData);

        if (!$response->successful()) {
            Log::error('OpenAI API web search request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request_data' => $requestData
            ]);
            throw new \Exception('OpenAI API web search request failed: ' . $response->body());
        }

        $result = $response->json();
        
        // Логируем использование токенов для web search
        if (isset($result['usage'])) {
            Log::info('OpenAI Web Search API - токены использованы', [
                'model' => $model,
                'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
                'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
                'total_tokens' => $result['usage']['total_tokens'] ?? 0,
                'prompt_token_details' => $result['usage']['prompt_token_details'] ?? [],
                'completion_token_details' => $result['usage']['completion_token_details'] ?? []
            ]);
        }
        
        return [
            'content' => $result['choices'][0]['message']['content'],
            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
            'model' => $model,
        ];
    }

    /**
     * Безопасно добавить сообщение в thread с проверкой активных run
     *
     * @param string $threadId
     * @param string $content
     * @param int $maxRetries
     * @return array
     */
    public function safeAddMessageToThread(string $threadId, string $content, int $maxRetries = 10): array
    {
        $attempts = 0;
        $initialDelay = 3; // Начальная задержка увеличена
        
        while ($attempts < $maxRetries) {
            try {
                // Проверяем активные run в thread
                $activeRuns = $this->getDetailedActiveRuns($threadId);
                if (!empty($activeRuns)) {
                    Log::warning('Thread имеет активные run, ожидаем завершения...', [
                        'thread_id' => $threadId,
                        'attempt' => $attempts + 1,
                        'max_attempts' => $maxRetries,
                        'active_runs' => $activeRuns
                    ]);
                    
                    // Увеличенная экспоненциальная задержка
                    $delay = min(15, $initialDelay * pow(1.5, $attempts));
                    sleep($delay);
                    $attempts++;
                    continue;
                }
                
                Log::info('Thread свободен, пытаемся добавить сообщение', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts + 1
                ]);
                
                // Пытаемся добавить сообщение
                return $this->addMessageToThread($threadId, $content);
                
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                if (strpos($errorMessage, 'while a run') !== false && strpos($errorMessage, 'is active') !== false) {
                    Log::warning('Попытка добавить сообщение в thread с активным run (по ошибке API)', [
                        'thread_id' => $threadId,
                        'attempt' => $attempts + 1,
                        'max_attempts' => $maxRetries,
                        'error' => $errorMessage
                    ]);
                    
                    // Еще более агрессивная задержка при получении ошибки от API
                    $delay = min(20, ($initialDelay + 2) * pow(1.8, $attempts));
                    sleep($delay);
                    $attempts++;
                    continue;
                }
                
                // Если это другая ошибка, пробрасываем её
                Log::error('Неожиданная ошибка при добавлении сообщения в thread', [
                    'thread_id' => $threadId,
                    'attempt' => $attempts + 1,
                    'error' => $errorMessage
                ]);
                throw $e;
            }
        }
        
        throw new \Exception("Не удалось добавить сообщение в thread после {$maxRetries} попыток. Thread постоянно имеет активные run.");
    }

    /**
     * Получить подробную информацию об активных run в thread
     *
     * @param string $threadId
     * @return array
     */
    private function getDetailedActiveRuns(string $threadId): array
    {
        try {
            $response = $this->getHttpClient([
                'OpenAI-Beta' => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs");

            if (!$response->successful()) {
                Log::warning('Не удалось получить список run для thread', [
                    'thread_id' => $threadId,
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                return [];
            }

            $runs = $response->json();
            $activeRuns = [];
            
            // Собираем подробную информацию об активных run
            foreach ($runs['data'] ?? [] as $run) {
                if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                    $activeRuns[] = [
                        'id' => $run['id'],
                        'status' => $run['status'],
                        'created_at' => $run['created_at'] ?? null,
                        'assistant_id' => $run['assistant_id'] ?? null
                    ];
                }
            }
            
            if (!empty($activeRuns)) {
                Log::info('Найдены активные run в thread', [
                    'thread_id' => $threadId,
                    'active_runs_count' => count($activeRuns),
                    'active_runs' => $activeRuns
                ]);
            }
            
            return $activeRuns;
            
        } catch (\Exception $e) {
            Log::error('Ошибка при получении подробной информации об активных run', [
                'thread_id' => $threadId,
                'error' => $e->getMessage()
            ]);
            
            // В случае ошибки предполагаем, что активных run нет
            return [];
        }
    }

    /**
     * Проверить наличие активных run в thread
     *
     * @param string $threadId
     * @return bool
     */
    public function hasActiveRuns(string $threadId): bool
    {
        return !empty($this->getDetailedActiveRuns($threadId));
    }
} 