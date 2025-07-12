<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clean-duplicates {--document-id= : ID документа для очистки} {--dry-run : Только показать что будет удалено}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Очистить дублирующие задачи генерации документов из очереди';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentId = $this->option('document-id');
        $dryRun = $this->option('dry-run');
        
        $this->info('🔍 Поиск дублирующих задач генерации документов...');
        
        $query = DB::table('jobs')
            ->where('payload', 'like', '%StartFullGenerateDocument%');
            
        if ($documentId) {
            $query->where('payload', 'like', '%"document_id":' . $documentId . '%');
            $this->info("Фильтр по документу ID: {$documentId}");
        }
        
        $jobs = $query->get();
        
        if ($jobs->isEmpty()) {
            $this->info('✅ Дублирующие задачи не найдены');
            return 0;
        }
        
        $this->info("Найдено задач: " . $jobs->count());
        
        // Группируем по document_id
        $groupedJobs = [];
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $docId = $payload['data']['document']['id'] ?? null;
            
            if ($docId) {
                if (!isset($groupedJobs[$docId])) {
                    $groupedJobs[$docId] = [];
                }
                $groupedJobs[$docId][] = $job;
            }
        }
        
        $totalDuplicates = 0;
        $documentsWithDuplicates = [];
        
        foreach ($groupedJobs as $docId => $docJobs) {
            if (count($docJobs) > 1) {
                $documentsWithDuplicates[$docId] = $docJobs;
                $totalDuplicates += count($docJobs) - 1; // Оставляем одну задачу
            }
        }
        
        if (empty($documentsWithDuplicates)) {
            $this->info('✅ Дублирующие задачи не найдены');
            return 0;
        }
        
        $this->warn("🚨 Найдено документов с дублирующими задачами: " . count($documentsWithDuplicates));
        $this->warn("🚨 Всего дублирующих задач для удаления: {$totalDuplicates}");
        
        // Показываем детали
        $headers = ['Документ ID', 'Количество задач', 'Job IDs', 'Создано'];
        $rows = [];
        
        foreach ($documentsWithDuplicates as $docId => $docJobs) {
            $jobIds = collect($docJobs)->pluck('id')->toArray();
            $createdTimes = collect($docJobs)->pluck('created_at')->toArray();
            
            $rows[] = [
                $docId,
                count($docJobs),
                implode(', ', $jobIds),
                implode(', ', array_map(function($timestamp) {
                    return date('H:i:s', $timestamp);
                }, $createdTimes))
            ];
        }
        
        $this->table($headers, $rows);
        
        if ($dryRun) {
            $this->info('🔍 Режим проверки: задачи НЕ будут удалены');
            
            foreach ($documentsWithDuplicates as $docId => $docJobs) {
                // Сортируем по времени создания, оставляем самую старую
                $sortedJobs = collect($docJobs)->sortBy('created_at');
                $toKeep = $sortedJobs->first();
                $toDelete = $sortedJobs->slice(1);
                
                $this->line("Документ {$docId}:");
                $this->line("  Оставить: Job #{$toKeep->id} (создан: " . date('H:i:s', $toKeep->created_at) . ")");
                foreach ($toDelete as $job) {
                    $this->line("  Удалить: Job #{$job->id} (создан: " . date('H:i:s', $job->created_at) . ")");
                }
            }
            
            return 0;
        }
        
        if (!$this->confirm('Удалить дублирующие задачи? (оставим самую старую задачу для каждого документа)')) {
            $this->info('Операция отменена');
            return 0;
        }
        
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
        
        return 0;
    }
} 