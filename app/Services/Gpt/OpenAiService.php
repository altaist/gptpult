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
        
        Log::info('OpenAI web search response received', [
            'finish_reason' => $result['choices'][0]['finish_reason'] ?? 'unknown',
            'has_annotations' => isset($result['choices'][0]['message']['annotations']),
            'usage' => $result['usage'] ?? []
        ]);

        return $result;
    }
} 