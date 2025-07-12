<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueMonitorRealtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor-realtime {--interval=5 : Интервал обновления в секундах} {--document-id= : ID документа для фильтрации}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Мониторинг состояния очереди в реальном времени с детальной информацией';

    private $startTime;
    private $lastJobsCount = 0;
    private $processedJobs = 0;
    private $failedJobs = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = now();
        $interval = (int) $this->option('interval');
        $documentId = $this->option('document-id');
        
        $this->info('🔄 Запуск мониторинга очереди в реальном времени');
        $this->info("📊 Интервал обновления: {$interval} секунд");
        
        if ($documentId) {
            $this->info("🎯 Фильтр по документу ID: {$documentId}");
        }
        
        $this->info('📋 Для остановки нажмите Ctrl+C');
        $this->line('');
        
        while (true) {
            $this->clearScreen();
            $this->displayHeader();
            $this->displayQueueStats($documentId);
            $this->displayActiveJobs($documentId);
            $this->displayRecentActivity($documentId);
            $this->displaySystemInfo();
            
            sleep($interval);
        }
        
        return 0;
    }
    
    private function clearScreen()
    {
        // Очистка экрана для Unix/Linux/Mac
        if (PHP_OS_FAMILY !== 'Windows') {
            system('clear');
        } else {
            system('cls');
        }
    }
    
    private function displayHeader()
    {
        $uptime = $this->startTime->diffForHumans(now(), true);
        $this->info("🚀 МОНИТОРИНГ ОЧЕРЕДИ LARAVEL - Время работы: {$uptime}");
        $this->info('⏰ Последнее обновление: ' . now()->format('Y-m-d H:i:s'));
        $this->line('');
    }
    
    private function displayQueueStats($documentId = null)
    {
        $this->info('📈 СТАТИСТИКА ОЧЕРЕДИ:');
        $this->line('');
        
        // Активные задачи
        $activeJobs = DB::table('jobs');
        if ($documentId) {
            $activeJobs->where('payload', 'like', '%"document_id":' . $documentId . '%');
        }
        $activeJobsCount = $activeJobs->count();
        
        // Неудачные задачи
        $failedJobs = DB::table('failed_jobs');
        if ($documentId) {
            $failedJobs->where('payload', 'like', '%"document_id":' . $documentId . '%');
        }
        $failedJobsCount = $failedJobs->count();
        
        // Статистика по очередям
        $queueStats = DB::table('jobs')
            ->select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->get();
            
        $this->line("📋 Активные задачи: {$activeJobsCount}");
        $this->line("❌ Неудачные задачи: {$failedJobsCount}");
        $this->line("📊 Обработано с начала мониторинга: {$this->processedJobs}");
        
        $this->line('');
        $this->info('📊 По очередям:');
        foreach ($queueStats as $stat) {
            $this->line("   {$stat->queue}: {$stat->count} задач");
        }
        
        $this->line('');
    }
    
    private function displayActiveJobs($documentId = null)
    {
        $this->info('🔄 АКТИВНЫЕ ЗАДАЧИ:');
        $this->line('');
        
        $query = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'created_at', 'available_at', 'attempts')
            ->orderBy('created_at', 'desc')
            ->limit(10);
            
        if ($documentId) {
            $query->where('payload', 'like', '%"document_id":' . $documentId . '%');
        }
        
        $jobs = $query->get();
        
        if ($jobs->isEmpty()) {
            $this->line('   Нет активных задач');
        } else {
            $headers = ['ID', 'Очередь', 'Класс', 'Документ', 'Создана', 'Доступна', 'Попытки'];
            $rows = [];
            
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? $payload['job'] ?? 'Unknown';
                $documentIdFromPayload = $payload['data']['document']['id'] ?? 'N/A';
                
                $rows[] = [
                    $job->id,
                    $job->queue,
                    $this->truncateString($jobClass, 25),
                    $documentIdFromPayload,
                    Carbon::createFromTimestamp($job->created_at)->format('H:i:s'),
                    Carbon::createFromTimestamp($job->available_at)->format('H:i:s'),
                    $job->attempts
                ];
            }
            
            $this->table($headers, $rows);
        }
        
        $this->line('');
    }
    
    private function displayRecentActivity($documentId = null)
    {
        $this->info('📝 ПОСЛЕДНИЕ СОБЫТИЯ (из queue_operations.log):');
        $this->line('');
        
        $logFile = storage_path('logs/queue_operations.log');
        
        if (!file_exists($logFile)) {
            $this->line('   Лог файл не найден');
            $this->line('');
            return;
        }
        
        $lines = [];
        $handle = fopen($logFile, 'r');
        
        if ($handle) {
            // Читаем последние 20 строк
            $buffer = '';
            $pos = -1;
            $lineCount = 0;
            
            fseek($handle, $pos, SEEK_END);
            
            while ($lineCount < 20 && ftell($handle) > 0) {
                $char = fgetc($handle);
                if ($char === "\n") {
                    if (!empty(trim($buffer))) {
                        $lines[] = strrev($buffer);
                        $lineCount++;
                    }
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                fseek($handle, --$pos, SEEK_END);
            }
            
            if (!empty(trim($buffer))) {
                $lines[] = strrev($buffer);
            }
            
            fclose($handle);
        }
        
        $lines = array_reverse($lines);
        
        // Фильтруем по document_id если указан
        if ($documentId) {
            $lines = array_filter($lines, function($line) use ($documentId) {
                return strpos($line, '"document_id":' . $documentId) !== false;
            });
        }
        
        $displayLines = array_slice($lines, -10); // Показываем последние 10
        
        foreach ($displayLines as $line) {
            if (strpos($line, '🔄 JOB QUEUED') !== false) {
                $this->line('<fg=blue>   ' . $this->extractLogInfo($line) . '</>');
            } elseif (strpos($line, '▶️ JOB PROCESSING') !== false) {
                $this->line('<fg=yellow>   ' . $this->extractLogInfo($line) . '</>');
            } elseif (strpos($line, '✅ JOB PROCESSED') !== false) {
                $this->line('<fg=green>   ' . $this->extractLogInfo($line) . '</>');
            } elseif (strpos($line, '❌ JOB FAILED') !== false) {
                $this->line('<fg=red>   ' . $this->extractLogInfo($line) . '</>');
            } elseif (strpos($line, '🚀 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ') !== false) {
                $this->line('<fg=cyan>   ' . $this->extractLogInfo($line) . '</>');
            } elseif (strpos($line, '🌐 API ЗАПРОС') !== false) {
                $this->line('<fg=magenta>   ' . $this->extractLogInfo($line) . '</>');
            } else {
                $this->line('   ' . $this->extractLogInfo($line));
            }
        }
        
        $this->line('');
    }
    
    private function displaySystemInfo()
    {
        $this->info('💻 СИСТЕМНАЯ ИНФОРМАЦИЯ:');
        $this->line('');
        
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        
        $this->line("🧠 Использование памяти: {$memoryUsage} MB (пик: {$memoryPeak} MB)");
        $this->line("🔧 PHP версия: " . PHP_VERSION);
        $this->line("🆔 Process ID: " . getmypid());
        
        // Информация о воркерах
        $this->line('');
        $this->info('👷 ВОРКЕРЫ:');
        
        // Пытаемся получить информацию о запущенных воркерах
        $processes = [];
        if (function_exists('exec')) {
            exec('ps aux | grep "queue:work" | grep -v grep', $processes);
        }
        
        if (empty($processes)) {
            $this->line('   Информация о воркерах недоступна');
        } else {
            $this->line("   Найдено воркеров: " . count($processes));
            foreach (array_slice($processes, 0, 3) as $process) {
                $this->line('   ' . $this->truncateString($process, 80));
            }
        }
    }
    
    private function extractLogInfo($line)
    {
        // Извлекаем время и основную информацию из строки лога
        if (preg_match('/\[(.*?)\].*?production\.\w+:\s*(.+)/', $line, $matches)) {
            $time = Carbon::parse($matches[1])->format('H:i:s');
            $message = $matches[2];
            
            // Пытаемся извлечь JSON и получить полезную информацию
            if (preg_match('/({.*})/', $message, $jsonMatches)) {
                $data = json_decode($jsonMatches[1], true);
                if ($data && isset($data['document_id'])) {
                    $docId = $data['document_id'];
                    $event = $data['event'] ?? 'unknown';
                    return "{$time} [{$event}] Doc:{$docId}";
                }
            }
            
            return "{$time} {$message}";
        }
        
        return $this->truncateString($line, 100);
    }
    
    private function truncateString($string, $length)
    {
        return strlen($string) > $length ? substr($string, 0, $length - 3) . '...' : $string;
    }
} 