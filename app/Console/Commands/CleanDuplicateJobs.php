<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CleanDuplicateJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clean-duplicates 
                            {--document-id= : ID документа для очистки} 
                            {--dry-run : Только показать что будет удалено}
                            {--clean-processes : Очистить зависшие процессы из кэша}
                            {--max-process-age=900 : Максимальный возраст процесса в секундах (по умолчанию 15 минут)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить дублирующие задачи генерации документов из очереди и зависшие процессы';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->option('document-id');
        $dryRun = $this->option('dry-run');
        $cleanProcesses = $this->option('clean-processes');
        $maxProcessAge = (int) $this->option('max-process-age');
        
        $this->info('🔍 Поиск дублирующих задач генерации документов...');
        
        // Очистка дублирующих задач из очереди
        $this->cleanDuplicateJobs($documentId, $dryRun);
        
        // Очистка зависших процессов из кэша
        if ($cleanProcesses) {
            $this->line('');
            $this->info('🧹 Очистка зависших процессов из кэша...');
            $this->cleanStuckProcesses($documentId, $dryRun, $maxProcessAge);
        }
        
        return 0;
    }
    
    /**
     * Очистить дублирующие задачи из очереди
     */
    private function cleanDuplicateJobs($documentId, $dryRun)
    {
        $query = DB::table('jobs')
            ->where('payload', 'like', '%StartFullGenerateDocument%');
            
        if ($documentId) {
            $query->where('payload', 'like', '%"document_id":' . $documentId . '%');
            $this->info("Фильтр по документу ID: {$documentId}");
        }
        
        $jobs = $query->get();
        
        if ($jobs->isEmpty()) {
            $this->info('✅ Дублирующие задачи не найдены');
            return;
        }
        
        $this->info("Найдено задач: " . $jobs->count());
        
        // Группируем по document_id
        $groupedJobs = [];
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $docId = null;
            
            // Извлекаем document_id из payload
            if (isset($payload['data']['document']['id'])) {
                $docId = $payload['data']['document']['id'];
            } elseif (isset($payload['data']['document_id'])) {
                $docId = $payload['data']['document_id'];
            }
            
            if ($docId) {
                if (!isset($groupedJobs[$docId])) {
                    $groupedJobs[$docId] = [];
                }
                $groupedJobs[$docId][] = $job;
            }
        }
        
        // Ищем дубли
        $documentsWithDuplicates = [];
        foreach ($groupedJobs as $docId => $docJobs) {
            if (count($docJobs) > 1) {
                $documentsWithDuplicates[$docId] = $docJobs;
            }
        }
        
        if (empty($documentsWithDuplicates)) {
            $this->info('✅ Дублирующие задачи не найдены');
            return;
        }
        
        $this->info("Найдены дубли для документов: " . implode(', ', array_keys($documentsWithDuplicates)));
        
        if ($dryRun) {
            $this->info('🔍 РЕЖИМ ПРОСМОТРА (--dry-run):');
            foreach ($documentsWithDuplicates as $docId => $docJobs) {
                $this->line("Документ {$docId}: " . count($docJobs) . " дублирующих задач");
                foreach ($docJobs as $job) {
                    $this->line("  - Job #{$job->id} (создана: " . date('Y-m-d H:i:s', $job->created_at) . ")");
                }
            }
            return;
        }
        
        // Удаляем дубли
        $deletedCount = 0;
        foreach ($documentsWithDuplicates as $docId => $docJobs) {
            // Сортируем по времени создания, оставляем самую старую
            $sortedJobs = collect($docJobs)->sortBy('created_at');
            $toKeep = $sortedJobs->first();
            $toDelete = $sortedJobs->slice(1);
            
            $this->line("Документ {$docId}: оставляем Job #{$toKeep->id}, удаляем " . $toDelete->count() . " дублей");
            
            foreach ($toDelete as $job) {
                DB::table('jobs')->where('id', $job->id)->delete();
                $deletedCount++;
                $this->line("  ✅ Удален Job #{$job->id}");
            }
        }
        
        $this->info("🎉 Удалено дублирующих задач: {$deletedCount}");
    }
    
    /**
     * Очистить зависшие процессы из кэша
     */
    private function cleanStuckProcesses($documentId, $dryRun, $maxProcessAge)
    {
        $processTypes = ['full_generation_process', 'base_generation_process'];
        $stuckProcesses = [];
        $currentTime = now();
        
        // Проверяем процессы для документов
        $documentsToCheck = $documentId ? [$documentId] : range(1, 100);
        
        foreach ($documentsToCheck as $docId) {
            foreach ($processTypes as $processType) {
                $processKey = "{$processType}_{$docId}";
                
                if (Cache::has($processKey)) {
                    $processInfo = Cache::get($processKey);
                    
                    // Проверяем возраст процесса
                    if (isset($processInfo['started_at'])) {
                        $startedAt = \Carbon\Carbon::parse($processInfo['started_at']);
                        $ageInSeconds = $currentTime->diffInSeconds($startedAt);
                        
                        if ($ageInSeconds > $maxProcessAge) {
                            $stuckProcesses[] = [
                                'key' => $processKey,
                                'document_id' => $docId,
                                'type' => $processType,
                                'info' => $processInfo,
                                'age_seconds' => $ageInSeconds,
                                'age_minutes' => round($ageInSeconds / 60, 1)
                            ];
                        }
                    } else {
                        // Процесс без времени запуска считается зависшим
                        $stuckProcesses[] = [
                            'key' => $processKey,
                            'document_id' => $docId,
                            'type' => $processType,
                            'info' => $processInfo,
                            'age_seconds' => null,
                            'age_minutes' => 'неизвестно'
                        ];
                    }
                }
            }
        }
        
        if (empty($stuckProcesses)) {
            $this->info('✅ Зависшие процессы не найдены');
            return;
        }
        
        $this->info("Найдено зависших процессов: " . count($stuckProcesses));
        
        if ($dryRun) {
            $this->info('🔍 РЕЖИМ ПРОСМОТРА (--dry-run):');
            foreach ($stuckProcesses as $process) {
                $type = $process['type'] === 'full_generation_process' ? 'ПОЛНАЯ' : 'БАЗОВАЯ';
                $this->line("Документ {$process['document_id']} ({$type}):");
                $this->line("  Возраст: {$process['age_minutes']} минут");
                $this->line("  PID: " . ($process['info']['process_id'] ?? 'неизвестно'));
                $this->line("  Job ID: " . ($process['info']['job_id'] ?? 'неизвестно'));
                $this->line("  Ключ кэша: {$process['key']}");
                $this->line('');
            }
            return;
        }
        
        // Удаляем зависшие процессы
        $cleanedCount = 0;
        foreach ($stuckProcesses as $process) {
            $type = $process['type'] === 'full_generation_process' ? 'ПОЛНАЯ' : 'БАЗОВАЯ';
            
            Cache::forget($process['key']);
            
            // Также очищаем соответствующую блокировку
            $lockKey = str_replace('_process_', '_lock_', $process['key']);
            Cache::lock($lockKey)->release();
            
            $cleanedCount++;
            $this->line("✅ Очищен процесс: Документ {$process['document_id']} ({$type})");
        }
        
        $this->info("🎉 Очищено зависших процессов: {$cleanedCount}");
    }
} 