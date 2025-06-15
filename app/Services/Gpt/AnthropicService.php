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
            'claude-3-opus-20240229' => 'Claude 3 Opus',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku',
        ];
    }
} 