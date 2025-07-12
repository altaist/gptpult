<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Services\Gpt\GptServiceFactory;
use App\Services\Gpt\OpenAiService;
use Illuminate\Support\Facades\Log;

class ThreadMonitor extends Command
{
    protected $signature = 'debug:thread-monitor {thread_id} {--interval=5} {--duration=60}';
    protected $description = 'Мониторинг состояния thread на протяжении времени';
    
    private $logChannel = 'debug_generation';

    public function handle()
    {
        $threadId = $this->argument('thread_id');
        $interval = (int)$this->option('interval'); // секунды между проверками
        $duration = (int)$this->option('duration'); // общее время мониторинга
        
        $this->info("=== МОНИТОРИНГ THREAD {$threadId} ===");
        $this->info("Интервал: {$interval}с, Продолжительность: {$duration}с");
        
        // Инициализируем GPT сервис
        $factory = app(GptServiceFactory::class);
        $gptService = $factory->make('openai');
        
        $startTime = time();
        $checkCount = 0;
        
        while (time() - $startTime < $duration) {
            $checkCount++;
            $elapsed = time() - $startTime;
            
            $this->info("\n--- Проверка #{$checkCount} (+{$elapsed}s) ---");
            
            try {
                $this->checkThreadState($gptService, $threadId, $checkCount);
                
                if ($elapsed + $interval < $duration) {
                    $this->info("Ожидаем {$interval} секунд...");
                    sleep($interval);
                }
                
            } catch (\Exception $e) {
                $this->error("Ошибка при проверке #{$checkCount}: " . $e->getMessage());
                Log::channel($this->logChannel)->error("Ошибка мониторинга thread", [
                    'thread_id' => $threadId,
                    'check_count' => $checkCount,
                    'error' => $e->getMessage()
                ]);
                
                sleep($interval);
            }
        }
        
        $this->info("\n=== МОНИТОРИНГ ЗАВЕРШЕН ===");
        $this->info("Всего проверок: {$checkCount}");
        $this->info("Время работы: " . (time() - $startTime) . "s");
    }

    private function checkThreadState(OpenAiService $gptService, string $threadId, int $checkNumber)
    {
        // Получаем все run для thread через рефлексию
        $reflection = new \ReflectionClass($gptService);
        $method = $reflection->getMethod('getHttpClient');
        $method->setAccessible(true);
        $httpClient = $method->invoke($gptService, [
            'OpenAI-Beta' => 'assistants=v2',
        ]);
        
        $response = $httpClient->get("https://api.openai.com/v1/threads/{$threadId}/runs");

        if (!$response->successful()) {
            throw new \Exception("Не удалось получить состояние thread: " . $response->status());
        }

        $runs = $response->json();
        $activeRuns = [];
        $completedRuns = [];
        $failedRuns = [];
        $otherRuns = [];

        foreach ($runs['data'] ?? [] as $run) {
            $runInfo = [
                'id' => substr($run['id'], 0, 12) . '...',
                'status' => $run['status'],
                'created' => $run['created_at'] ?? null,
                'started' => $run['started_at'] ?? null,
                'completed' => $run['completed_at'] ?? null,
                'model' => $run['model'] ?? 'unknown'
            ];

            if (in_array($run['status'], ['queued', 'in_progress', 'requires_action'])) {
                $activeRuns[] = $runInfo;
            } elseif ($run['status'] === 'completed') {
                $completedRuns[] = $runInfo;
            } elseif (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                $failedRuns[] = $runInfo;
            } else {
                $otherRuns[] = $runInfo;
            }
        }

        // Вывод результатов
        $this->info("Thread ID: {$threadId}");
        $this->info("Всего run: " . count($runs['data'] ?? []));
        
        if (!empty($activeRuns)) {
            $this->warn("🔴 Активные run (" . count($activeRuns) . "):");
            foreach ($activeRuns as $run) {
                $this->warn("  - {$run['id']} [{$run['status']}] {$run['model']}");
            }
        } else {
            $this->info("🟢 Нет активных run");
        }
        
        if (!empty($completedRuns)) {
            $this->info("✅ Завершенные run (" . count($completedRuns) . "):");
            foreach (array_slice($completedRuns, 0, 3) as $run) {
                $this->info("  - {$run['id']} [{$run['status']}] {$run['model']}");
            }
            if (count($completedRuns) > 3) {
                $this->info("  ... и еще " . (count($completedRuns) - 3) . " завершенных");
            }
        }
        
        if (!empty($failedRuns)) {
            $this->warn("❌ Неуспешные run (" . count($failedRuns) . "):");
            foreach ($failedRuns as $run) {
                $this->warn("  - {$run['id']} [{$run['status']}] {$run['model']}");
            }
        }

        // Проверка через hasActiveRuns
        $hasActiveViaMethod = $gptService->hasActiveRuns($threadId);
        $manualCheckHasActive = count($activeRuns) > 0;
        
        if ($hasActiveViaMethod !== $manualCheckHasActive) {
            $this->error("⚠️ НЕСООТВЕТСТВИЕ: hasActiveRuns()={$hasActiveViaMethod}, manual={$manualCheckHasActive}");
        }

        // Логируем детальную информацию
        Log::channel($this->logChannel)->info("Thread monitor check #{$checkNumber}", [
            'thread_id' => $threadId,
            'check_number' => $checkNumber,
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'total_runs' => count($runs['data'] ?? []),
            'active_runs' => count($activeRuns),
            'completed_runs' => count($completedRuns),
            'failed_runs' => count($failedRuns),
            'other_runs' => count($otherRuns),
            'has_active_via_method' => $hasActiveViaMethod,
            'manual_check_has_active' => $manualCheckHasActive,
            'method_matches_manual' => $hasActiveViaMethod === $manualCheckHasActive,
            'active_runs_details' => $activeRuns,
            'all_runs' => array_map(function($run) {
                return [
                    'id' => $run['id'],
                    'status' => $run['status'],
                    'created_at' => $run['created_at'] ?? null,
                    'started_at' => $run['started_at'] ?? null,
                    'completed_at' => $run['completed_at'] ?? null,
                    'model' => $run['model'] ?? 'unknown'
                ];
            }, $runs['data'] ?? [])
        ]);
    }
} 