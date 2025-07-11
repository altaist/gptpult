<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorThreadRuns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'thread:monitor-runs 
                           {--document_id= : ID документа для мониторинга}
                           {--thread_id= : Thread ID для мониторинга}
                           {--continuous : Непрерывный мониторинг}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Мониторинг активных run в OpenAI thread\'ах';

    /**
     * Execute the console command.
     */
    public function handle(GptServiceFactory $factory)
    {
        $documentId = $this->option('document_id');
        $threadId = $this->option('thread_id');
        $continuous = $this->option('continuous');

        if (!$documentId && !$threadId) {
            $this->error('Укажите --document_id или --thread_id');
            return 1;
        }

        if ($documentId) {
            $document = Document::find($documentId);
            if (!$document) {
                $this->error("Документ с ID {$documentId} не найден");
                return 1;
            }
            $threadId = $document->thread_id;
            if (!$threadId) {
                $this->error("У документа {$documentId} нет thread_id");
                return 1;
            }
        }

        $gptService = $factory->make('openai');

        $this->info("🔍 Мониторинг thread: {$threadId}");
        
        if ($continuous) {
            $this->info("⏰ Непрерывный мониторинг запущен. Нажмите Ctrl+C для остановки.");
            
            while (true) {
                $this->checkThreadRuns($gptService, $threadId);
                sleep(5);
            }
        } else {
            $this->checkThreadRuns($gptService, $threadId);
        }

        return 0;
    }

    /**
     * Проверить активные run в thread
     */
    private function checkThreadRuns($gptService, string $threadId): void
    {
        try {
            // Используем прямой HTTP запрос вместо приватного метода
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/runs");

            if (!$response->successful()) {
                $this->error("❌ Ошибка получения run: HTTP {$response->status()}");
                $this->line($response->body());
                return;
            }

            $runs = $response->json();
            $activeRuns = [];
            $recentRuns = [];

            $now = time();

            foreach ($runs['data'] ?? [] as $run) {
                $createdAt = $run['created_at'] ?? 0;
                $age = $now - $createdAt;
                
                if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                    $activeRuns[] = $run;
                }
                
                // Показываем run за последние 10 минут
                if ($age <= 600) {
                    $recentRuns[] = $run;
                }
            }

            $this->line("📊 " . now()->format('H:i:s') . " - Thread: {$threadId}");
            
            if (!empty($activeRuns)) {
                $this->error("🔴 Активных run: " . count($activeRuns));
                foreach ($activeRuns as $run) {
                    $age = $now - ($run['created_at'] ?? 0);
                    $this->line("  - Run {$run['id']}: {$run['status']} (возраст: {$age}с)");
                }
            } else {
                $this->info("✅ Активных run нет");
            }

            if (!empty($recentRuns)) {
                $this->line("📋 Недавние run (10 мин):");
                foreach (array_slice($recentRuns, 0, 5) as $run) {
                    $age = $now - ($run['created_at'] ?? 0);
                    $statusIcon = in_array($run['status'], ['queued', 'in_progress', 'requires_action']) ? '🔴' : '✅';
                    $this->line("  {$statusIcon} {$run['id']}: {$run['status']} ({$age}с назад)");
                }
            }

            $this->line("");

        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
        }
    }
} 