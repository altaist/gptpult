<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WorkerManagementService
{
    /**
     * Получить список запущенных воркеров
     */
    public function getRunningWorkers(): array
    {
        try {
            $result = Process::run('ps aux | grep "queue:work" | grep -v grep');
            
            if (!$result->successful()) {
                return [];
            }

            $workers = [];
            $lines = explode("\n", trim($result->output()));
            
            foreach ($lines as $line) {
                if (empty($line)) continue;
                
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 11) {
                    $worker = $this->parseWorkerLine($parts);
                    if ($worker) {
                        $workers[] = $worker;
                    }
                }
            }
            
            return $workers;
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения списка воркеров', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Запустить воркер для очереди
     */
    public function startWorker(string $queue, int $timeout = 600, array $options = []): array
    {
        try {
            // Проверяем, не запущен ли уже воркер для этой очереди
            $existingWorkers = $this->getWorkersForQueue($queue);
            
            if (count($existingWorkers) >= ($options['max_workers'] ?? 3)) {
                return [
                    'success' => false,
                    'message' => "Достигнуто максимальное количество воркеров для очереди {$queue}"
                ];
            }

            // Формируем команду запуска
            $command = $this->buildWorkerCommand($queue, $timeout, $options);
            
            // Запускаем воркер
            $result = Process::run($command);
            $pid = trim($result->output());
            
            if ($result->successful() && is_numeric($pid)) {
                // Сохраняем информацию о воркере в кэше
                $this->cacheWorkerInfo($pid, $queue, $timeout);
                
                Log::info('Воркер запущен', [
                    'queue' => $queue,
                    'pid' => $pid,
                    'timeout' => $timeout
                ]);
                
                return [
                    'success' => true,
                    'message' => "Воркер для очереди {$queue} запущен",
                    'pid' => $pid
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Не удалось запустить воркер'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка запуска воркера', [
                'queue' => $queue,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка запуска воркера: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Остановить воркер по PID
     */
    public function stopWorker(int $pid, bool $force = false): array
    {
        try {
            // Проверяем существование процесса
            if (!$this->isProcessRunning($pid)) {
                return [
                    'success' => false,
                    'message' => 'Процесс не найден'
                ];
            }

            // Отправляем сигнал остановки
            $signal = $force ? 'KILL' : 'TERM';
            $result = Process::run("kill -{$signal} {$pid}");
            
            if ($result->successful()) {
                // Удаляем информацию из кэша
                $this->removeCachedWorkerInfo($pid);
                
                Log::info('Воркер остановлен', [
                    'pid' => $pid,
                    'force' => $force
                ]);
                
                return [
                    'success' => true,
                    'message' => "Воркер с PID {$pid} остановлен"
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Не удалось остановить воркер'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка остановки воркера', [
                'pid' => $pid,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка остановки воркера: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Остановить все воркеры для очереди
     */
    public function stopQueueWorkers(string $queue): array
    {
        $workers = $this->getWorkersForQueue($queue);
        $results = [];
        
        foreach ($workers as $worker) {
            $result = $this->stopWorker($worker['pid']);
            $results[] = $result;
        }
        
        $successful = count(array_filter($results, fn($r) => $r['success']));
        
        return [
            'success' => $successful > 0,
            'message' => "Остановлено {$successful} из " . count($workers) . " воркеров",
            'details' => $results
        ];
    }

    /**
     * Перезапустить воркеры для очереди
     */
    public function restartQueueWorkers(string $queue, int $timeout = 600): array
    {
        // Останавливаем существующие воркеры
        $stopResult = $this->stopQueueWorkers($queue);
        
        // Ждем остановки
        sleep(2);
        
        // Запускаем новый воркер
        $startResult = $this->startWorker($queue, $timeout);
        
        return [
            'success' => $startResult['success'],
            'message' => "Воркеры для очереди {$queue} перезапущены",
            'stop_result' => $stopResult,
            'start_result' => $startResult
        ];
    }

    /**
     * Получить статистику воркеров
     */
    public function getWorkerStats(): array
    {
        $workers = $this->getRunningWorkers();
        
        $stats = [
            'total' => count($workers),
            'by_queue' => [],
            'memory_usage' => 0,
            'cpu_usage' => 0
        ];
        
        foreach ($workers as $worker) {
            $queue = $worker['queue'];
            
            if (!isset($stats['by_queue'][$queue])) {
                $stats['by_queue'][$queue] = 0;
            }
            
            $stats['by_queue'][$queue]++;
            $stats['memory_usage'] += (float) $worker['memory'];
            $stats['cpu_usage'] += (float) $worker['cpu'];
        }
        
        return $stats;
    }

    /**
     * Мониторинг состояния воркеров
     */
    public function monitorWorkers(): array
    {
        $workers = $this->getRunningWorkers();
        $issues = [];
        
        foreach ($workers as $worker) {
            // Проверка использования памяти (более 80%)
            if ((float) $worker['memory'] > 80) {
                $issues[] = [
                    'type' => 'high_memory',
                    'worker' => $worker,
                    'message' => "Воркер {$worker['pid']} использует {$worker['memory']}% памяти"
                ];
            }
            
            // Проверка использования CPU (более 90%)
            if ((float) $worker['cpu'] > 90) {
                $issues[] = [
                    'type' => 'high_cpu',
                    'worker' => $worker,
                    'message' => "Воркер {$worker['pid']} использует {$worker['cpu']}% CPU"
                ];
            }
            
            // Проверка времени работы (более 24 часов)
            $uptime = $this->calculateUptime($worker['start_time']);
            if ($uptime > 86400) { // 24 часа в секундах
                $issues[] = [
                    'type' => 'long_running',
                    'worker' => $worker,
                    'message' => "Воркер {$worker['pid']} работает более 24 часов"
                ];
            }
        }
        
        return [
            'workers' => $workers,
            'issues' => $issues,
            'healthy' => empty($issues)
        ];
    }

    /**
     * Вспомогательные методы
     */
    
    private function parseWorkerLine(array $parts): ?array
    {
        $pid = $parts[1];
        $cpu = $parts[2];
        $memory = $parts[3];
        $startTime = $parts[8];
        $command = implode(' ', array_slice($parts, 10));
        
        // Проверяем, что это действительно воркер Laravel
        if (!str_contains($command, 'queue:work') && !str_contains($command, 'artisan')) {
            return null;
        }
        
        // Извлекаем очередь из команды
        $queue = 'default';
        if (preg_match('/--queue[=\s]([^\s]+)/', $command, $matches)) {
            $queue = $matches[1];
        }
        
        return [
            'pid' => $pid,
            'queue' => $queue,
            'cpu' => $cpu,
            'memory' => $memory,
            'start_time' => $startTime,
            'command' => $command,
            'status' => 'running'
        ];
    }
    
    private function buildWorkerCommand(string $queue, int $timeout, array $options): string
    {
        $tries = $options['tries'] ?? 3;
        $sleep = $options['sleep'] ?? 3;
        $maxJobs = $options['max_jobs'] ?? 1000;
        
        return "nohup php artisan queue:work " .
               "--queue={$queue} " .
               "--timeout={$timeout} " .
               "--tries={$tries} " .
               "--sleep={$sleep} " .
               "--max-jobs={$maxJobs} " .
               "> /dev/null 2>&1 & echo $!";
    }
    
    private function getWorkersForQueue(string $queue): array
    {
        return array_filter(
            $this->getRunningWorkers(),
            fn($worker) => $worker['queue'] === $queue
        );
    }
    
    private function isProcessRunning(int $pid): bool
    {
        $result = Process::run("ps -p {$pid} -o pid=");
        return $result->successful() && !empty(trim($result->output()));
    }
    
    private function cacheWorkerInfo(string $pid, string $queue, int $timeout): void
    {
        Cache::put("worker_info_{$pid}", [
            'queue' => $queue,
            'timeout' => $timeout,
            'started_at' => now(),
        ], now()->addHours(24));
    }
    
    private function removeCachedWorkerInfo(string $pid): void
    {
        Cache::forget("worker_info_{$pid}");
    }
    
    private function calculateUptime(string $startTime): int
    {
        // Простая реализация - в реальности нужно правильно парсить время
        try {
            $start = Carbon::createFromFormat('H:i:s', $startTime);
            return now()->diffInSeconds($start);
        } catch (\Exception $e) {
            return 0;
        }
    }
} 