<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowJobsStatus extends Command
{
    protected $signature = 'jobs:status {--type= : Тип задания (StartGenerateDocument/StartFullGenerateDocument)}';
    protected $description = 'Показать статус последних заданий (в работе, failed, завершенные)';

    public function handle()
    {
        $jobTypes = ['StartGenerateDocument', 'StartFullGenerateDocument'];
        $selectedType = $this->option('type');
        
        if ($selectedType && !in_array($selectedType, $jobTypes)) {
            $this->error("Неверный тип задания. Доступные типы: " . implode(', ', $jobTypes));
            return 1;
        }

        // Активные задания
        $activeJobs = DB::table('jobs')
            ->select([
                DB::raw("'processing' as status"),
                'payload',
                'created_at',
                'attempts'
            ])
            ->when($selectedType, function ($query) use ($selectedType) {
                return $query->where('payload', 'like', '%' . $selectedType . '%');
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Failed задания
        $failedJobs = DB::table('failed_jobs')
            ->select([
                DB::raw("'failed' as status"),
                'payload',
                'failed_at as created_at',
                'attempts'
            ])
            ->when($selectedType, function ($query) use ($selectedType) {
                return $query->where('payload', 'like', '%' . $selectedType . '%');
            })
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get();

        // Объединяем и сортируем результаты
        $allJobs = collect()
            ->concat($activeJobs)
            ->concat($failedJobs)
            ->sortByDesc('created_at')
            ->take(10);

        // Подготавливаем данные для вывода
        $rows = $allJobs->map(function ($job) {
            $payload = json_decode($job->payload, true);
            $command = $payload['data']['command'] ?? 'Unknown';
            $documentId = $payload['data']['document_id'] ?? 'N/A';
            
            return [
                'type' => $command,
                'document_id' => $documentId,
                'status' => $job->status,
                'attempts' => $job->attempts,
                'created_at' => Carbon::parse($job->created_at)->format('Y-m-d H:i:s')
            ];
        });

        // Выводим результаты в виде таблицы
        $this->table(
            ['Тип', 'Document ID', 'Статус', 'Попытки', 'Создано'],
            $rows
        );

        return 0;
    }
} 