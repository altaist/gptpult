<?php

namespace App\Services\Gpt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService implements GptServiceInterface
{
    protected string $apiKey;
    protected string $defaultModel;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->defaultModel = config('services.anthropic.default_model', 'claude-3-opus-20240229');
    }

    public function sendRequest(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $temperature,
            'max_tokens' => $options['max_tokens'] ?? 4096,
        ]);

        if (!$response->successful()) {
            Log::error('Anthropic API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Anthropic API request failed: ' . $response->body());
        }

        $result = $response->json();
        
        // Логируем использование токенов
        if (isset($result['usage'])) {
            Log::info('Anthropic API - токены использованы', [
                'model' => $model,
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($result['usage']['input_tokens'] ?? 0) + ($result['usage']['output_tokens'] ?? 0)
            ]);
        }
        
        return [
            'content' => $result['content'][0]['text'],
            'tokens_used' => $result['usage']['output_tokens'] + $result['usage']['input_tokens'],
            'model' => $model,
        ];
    }

    public function getName(): string
    {
        return 'anthropic';
    }

    public function getAvailableModels(): array
    {
        return [
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-opus-20240229' => 'Claude 3 Opus',
        ];
    }

    public function createThread(): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function addMessageToThread(string $threadId, string $content): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function safeAddMessageToThread(string $threadId, string $content, int $maxRetries = 10): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function createRun(string $threadId, string $assistantId): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function waitForRunCompletion(string $threadId, string $runId): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function getThreadMessages(string $threadId): array
    {
        throw new \Exception('Assistants API не поддерживается для Anthropic');
    }

    public function generateWithWebSearch(array $messages, array $options = []): array
    {
        throw new \Exception('Веб-поиск не поддерживается для Anthropic');
    }
} 