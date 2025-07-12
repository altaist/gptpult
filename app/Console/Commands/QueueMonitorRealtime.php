<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
    private $interval;
    private $documentId;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = now();
        $this->interval = (int) $this->option('interval');
        $this->documentId = $this->option('document-id');
        
        $this->info('🔄 Запуск мониторинга очереди в реальном времени');
        $this->info("📊 Интервал обновления: {$this->interval} секунд");
        
        if ($this->documentId) {
            $this->info("🎯 Фильтр по документу ID: {$this->documentId}");
        }
        
        $this->info('📋 Для остановки нажмите Ctrl+C');
        $this->line('');
        
        $this->displayMonitoringInfo();
        
        return 0;
    }
    
    /**
     * Получить информацию о системе
     */
    private function getSystemInfo(): array
    {
        return [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'php_version' => PHP_VERSION,
            'server_load' => sys_getloadavg()[0] ?? 'N/A',
        ];
    }

    /**
     * Получить информацию о воркерах
     */
    private function getWorkersInfo(): array
    {
        // Получаем информацию о процессах PHP
        $output = [];
        $returnVar = 0;
        exec('ps aux | grep "queue:work\|artisan.*queue" | grep -v grep', $output, $returnVar);
        
        $workers = [];
        foreach ($output as $line) {
            if (preg_match('/(\d+)\s+[\d.]+\s+[\d.]+\s+[\d.]+\s+[\d:]+\s+(.+)/', $line, $matches)) {
                $workers[] = [
                    'pid' => $matches[1],
                    'command' => trim($matches[2])
                ];
            }
        }
        
        return $workers;
    }

    /**
     * Получить активные процессы генерации из кэша
     */
    private function getActiveProcesses(): array
    {
        $processes = [];
        
        // Получаем все ключи кэша, связанные с процессами генерации
        $cacheKeys = [
            'full_generation_process_*',
            'base_generation_process_*'
        ];
        
        // Проверяем известные процессы для документов
        for ($i = 1; $i <= 100; $i++) {
            $fullKey = "full_generation_process_{$i}";
            $baseKey = "base_generation_process_{$i}";
            
            if (Cache::has($fullKey)) {
                $processInfo = Cache::get($fullKey);
                $processes[] = [
                    'type' => 'full_generation',
                    'document_id' => $i,
                    'process_info' => $processInfo,
                    'cache_key' => $fullKey
                ];
            }
            
            if (Cache::has($baseKey)) {
                $processInfo = Cache::get($baseKey);
                $processes[] = [
                    'type' => 'base_generation',
                    'document_id' => $i,
                    'process_info' => $processInfo,
                    'cache_key' => $baseKey
                ];
            }
        }
        
        return $processes;
    }

    /**
     * Отобразить информацию о мониторинге
     */
    private function displayMonitoringInfo(): void
    {
        $this->line('');
        $this->line('<fg=cyan>📊 МОНИТОРИНГ ОЧЕРЕДИ В РЕАЛЬНОМ ВРЕМЕНИ</fg=cyan>');
        $this->line('<fg=gray>Нажмите Ctrl+C для выхода</fg=gray>');
        $this->line('');

        while (true) {
            // Очищаем экран
            $this->line("\033[2J\033[H");
            
            // Заголовок
            $this->line('<fg=cyan>📊 МОНИТОРИНГ ОЧЕРЕДИ</fg=cyan> ' . now()->format('Y-m-d H:i:s'));
            $this->line(str_repeat('=', 80));
            
            // Системная информация
            $systemInfo = $this->getSystemInfo();
            $this->line('<fg=yellow>🖥️  СИСТЕМА:</fg=yellow>');
            $this->line("   Время: {$systemInfo['timestamp']}");
            $this->line("   Память: {$systemInfo['memory_usage']} / Пик: {$systemInfo['memory_peak']}");
            $this->line("   PHP: {$systemInfo['php_version']} | Нагрузка: {$systemInfo['server_load']}");
            $this->line('');

            // Активные процессы из кэша
            $activeProcesses = $this->getActiveProcesses();
            $this->line('<fg=green>🔄 АКТИВНЫЕ ПРОЦЕССЫ ГЕНЕРАЦИИ:</fg=green>');
            if (empty($activeProcesses)) {
                $this->line('   <fg=gray>Нет активных процессов</fg=gray>');
            } else {
                foreach ($activeProcesses as $process) {
                    $type = $process['type'] === 'full_generation' ? 'ПОЛНАЯ' : 'БАЗОВАЯ';
                    $info = $process['process_info'];
                    $startedAt = $info['started_at'] ?? 'неизвестно';
                    $processId = $info['process_id'] ?? 'неизвестно';
                    $jobId = $info['job_id'] ?? 'неизвестно';
                    
                    $this->line("   📄 Документ {$process['document_id']} ({$type})");
                    $this->line("      PID: {$processId} | Job ID: {$jobId}");
                    $this->line("      Запущен: {$startedAt}");
                    $this->line('');
                }
            }

            // Активные задачи в очереди
            $activeJobs = $this->getActiveJobs();
            $this->line('<fg=blue>📋 АКТИВНЫЕ ЗАДАЧИ В ОЧЕРЕДИ:</fg=blue>');
            if ($activeJobs->isEmpty()) {
                $this->line('   <fg=gray>Нет активных задач</fg=gray>');
            } else {
                foreach ($activeJobs as $job) {
                    $payload = json_decode($job->payload, true);
                    $jobClass = $payload['displayName'] ?? 'Unknown';
                    $documentId = $this->extractDocumentId($payload);
                    
                    $this->line("   🔧 {$jobClass}");
                    $this->line("      ID: {$job->id} | Попытки: {$job->attempts}");
                    if ($documentId) {
                        $this->line("      Документ: {$documentId}");
                    }
                    $this->line("      Создана: " . date('Y-m-d H:i:s', $job->created_at));
                    $this->line('');
                }
            }

            // Статистика очередей
            $queueStats = $this->getQueueStats();
            $this->line('<fg=magenta>📈 СТАТИСТИКА ОЧЕРЕДЕЙ:</fg=magenta>');
            foreach ($queueStats as $queue => $stats) {
                $this->line("   {$queue}: {$stats['active']} активных | {$stats['failed']} неудачных");
            }
            $this->line('');

            // Информация о воркерах
            $workers = $this->getWorkersInfo();
            $this->line('<fg=cyan>👷 ВОРКЕРЫ:</fg=cyan>');
            if (empty($workers)) {
                $this->line('   <fg=red>Нет запущенных воркеров</fg=red>');
            } else {
                foreach ($workers as $worker) {
                    $this->line("   PID: {$worker['pid']} | {$worker['command']}");
                }
            }

            // Фильтр по документу
            if ($this->documentId) {
                $this->line('');
                $this->line('<fg=yellow>🔍 ФИЛЬТР ПО ДОКУМЕНТУ:</fg=yellow> ' . $this->documentId);
                $this->displayDocumentSpecificInfo($this->documentId);
            }

            // Последние события из логов
            $this->displayRecentLogEvents();

            $this->line('');
            $this->line('<fg=gray>Обновление каждые ' . $this->interval . ' секунд... (Ctrl+C для выхода)</fg=gray>');

            sleep($this->interval);
        }
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