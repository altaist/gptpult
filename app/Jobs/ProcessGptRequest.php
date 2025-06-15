<?php

namespace App\Jobs;

use App\Events\GptRequestCompleted;
use App\Events\GptRequestFailed;
use App\Models\GptRequest;
use App\Services\Gpt\GptServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessGptRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected GptRequest $gptRequest
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GptServiceFactory $factory): void
    {
        try {
            // Обновляем статус на "processing"
            $this->gptRequest->update(['status' => 'processing']);

            // Получаем сервис из фабрики
            $service = $factory->make($this->gptRequest->metadata['service'] ?? 'openai');

            // Отправчляем запрос
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $this->gptRequest->prompt]
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $this->gptRequest->update([
                    'status' => 'completed',
                    'response' => $response->json('choices.0.message.content'),
                    'metadata' => array_merge($this->gptRequest->metadata ?? [], [
                        'tokens_used' => $response->json('usage.total_tokens'),
                        'model' => $response->json('model'),
                        'service' => $service->getName(),
                    ]),
                ]);

                event(new GptRequestCompleted($this->gptRequest));
            } else {
                throw new \Exception('GPT API request failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('GPT request processing failed', [
                'request_id' => $this->gptRequest->id,
                'error' => $e->getMessage()
            ]);

            $this->gptRequest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            event(new GptRequestFailed($this->gptRequest, $e->getMessage()));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->gptRequest->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);

        event(new GptRequestFailed($this->gptRequest, $exception->getMessage()));
    }
} 