<?php

namespace App\Services\Gpt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService implements GptServiceInterface
{
    protected string $apiKey;
    protected ?string $organization;
    protected string $defaultModel;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->organization = config('services.openai.organization');
        $this->defaultModel = config('services.openai.default_model', 'gpt-3.5-turbo');
    }

    public function sendRequest(string $prompt, array $options = []): array
    {
        $model = $options['model'] ?? $this->defaultModel;
        $temperature = $options['temperature'] ?? 0.7;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
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
} 