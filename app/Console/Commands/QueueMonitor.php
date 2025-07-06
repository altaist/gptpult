<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class QueueMonitor extends Command
{
    protected $signature = 'queue:monitor-performance 
                           {--refresh=5 : Интервал обновления в секундах}
                           {--queue=document_creates : Очередь для мониторинга}';

    protected $description = 'Мониторинг производительности очередей в реальном времени';

    public function handle()
    {
        $queue = $this->option('queue');
        $refresh = (int) $this->option('refresh');
        
        $this->info("📊 Мониторинг очереди: {$queue}");
        $this->info("🔄 Обновление каждые {$refresh} секунд");
        $this->info("Нажмите Ctrl+C для остановки");
        $this->line('');
        
        $startTime = microtime(true);
        $totalProcessed = 0;
        $previousStats = null;
        
        while (true) {
            $currentTime = microtime(true);
            $uptime = $currentTime - $startTime;
            
            // Получаем статистику
            $stats = $this->getQueueStats($queue);
            
            // Очищаем экран
            $this->getOutput()->write("\033\143");
            
            // Заголовок
            $this->info("📊 Мониторинг очереди: {$queue}");
            $this->info("🕐 Время работы: " . gmdate('H:i:s', $uptime));
            $this->info("🔄 Обновлено: " . date('H:i:s'));
            $this->line('');
            
            // Основная статистика
            $this->line("📈 Основная статистика:");
            $this->line("   Ожидающих задач: " . $stats['pending']);
            $this->line("   Провалившихся: " . $stats['failed']);
            $this->line("   Всего обработано: " . $stats['total_processed']);
            $this->line('');
            
            // Производительность
            if ($previousStats) {
                $processed = $stats['total_processed'] - $previousStats['total_processed'];
                $rate = $processed / $refresh;
                $this->line("⚡ Производительность:");
                $this->line("   Обработано за {$refresh}с: " . $processed);
                $this->line("   Скорость: " . round($rate, 2) . " задач/сек");
                $this->line("   Средняя скорость: " . round($stats['total_processed'] / $uptime, 2) . " задач/сек");
                $this->line('');
            }
            
            // Статистика документов
            $docStats = $this->getDocumentStats();
            $this->line("📋 Статистика документов:");
            $this->line("   Генерируется: " . $docStats['generating']);
            $this->line("   Завершено: " . $docStats['completed']);
            $this->line("   Ошибок: " . $docStats['failed']);
            $this->line('');
            
            // Использование памяти
            $memoryUsage = memory_get_usage(true);
            $this->line("💾 Использование памяти:");
            $this->line("   Текущее: " . $this->formatBytes($memoryUsage));
            $this->line("   Пик: " . $this->formatBytes(memory_get_peak_usage(true)));
            $this->line('');
            
            // Активные worker'ы (если возможно определить)
            $activeWorkers = $this->getActiveWorkers();
            if ($activeWorkers > 0) {
                $this->line("👥 Активные worker'ы: " . $activeWorkers);
                $this->line('');
            }
            
            // Последние задачи
            $recentJobs = $this->getRecentJobs($queue, 5);
            if (!empty($recentJobs)) {
                $this->line("📝 Последние задачи:");
                foreach ($recentJobs as $job) {
                    $this->line("   " . $job['created_at'] . " - " . $job['job_type']);
                }
                $this->line('');
            }
            
            $previousStats = $stats;
            sleep($refresh);
        }
    }
    
    private function getQueueStats(string $queue): array
    {
        $connection = config('queue.default');
        
        if ($connection === 'database') {
            $pending = DB::table('jobs')
                ->where('queue', $queue)
                ->count();
                
            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();
                
            $totalProcessed = Cache::get("queue_stats_{$queue}_processed", 0);
            
        } else {
            // Для Redis и других драйверов
            $pending = Queue::size($queue);
            $failed = 0; // Сложнее получить для Redis
            $totalProcessed = Cache::get("queue_stats_{$queue}_processed", 0);
        }
        
        return [
            'pending' => $pending,
            'failed' => $failed,
            'total_processed' => $totalProcessed
        ];
    }
    
    private function getDocumentStats(): array
    {
        return [
            'generating' => DB::table('documents')
                ->whereIn('status', ['pre_generating', 'full_generating'])
                ->count(),
            'completed' => DB::table('documents')
                ->where('status', 'pre_generated')
                ->count(),
            'failed' => DB::table('documents')
                ->whereIn('status', ['pre_generation_failed', 'full_generation_failed'])
                ->count(),
        ];
    }
    
    private function getActiveWorkers(): int
    {
        // Примерная оценка на основе кэша
        $workers = 0;
        for ($i = 1; $i <= 10; $i++) {
            if (Cache::has("worker_heartbeat_{$i}")) {
                $workers++;
            }
        }
        return $workers;
    }
    
    private function getRecentJobs(string $queue, int $limit): array
    {
        return DB::table('jobs')
            ->where('queue', $queue)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'created_at' => date('H:i:s', $job->created_at),
                    'job_type' => $payload['displayName'] ?? 'Unknown'
                ];
            })
            ->toArray();
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 