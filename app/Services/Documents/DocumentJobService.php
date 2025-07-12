<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Jobs\StartGenerateDocument;
use App\Jobs\StartFullGenerateDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Orders\OrderService;
use App\Services\Orders\TransitionService;
use App\Enums\DocumentStatus;

class DocumentJobService
{
    /**
     * Типы заданий для генерации документов
     */
    protected const JOB_TYPES = [
        'StartGenerateDocument',
        'StartFullGenerateDocument'
    ];

    /**
     * Запустить базовую генерацию документа
     *
     * @param Document $document
     * @return void
     * @throws \Exception если уже есть активное задание
     */
    public function startBaseGeneration(Document $document): void
    {
        if ($this->hasActiveJob($document)) {
            throw new \Exception('Для этого документа уже запущена задача генерации');
        }

        Log::info('Запуск базовой генерации документа', [
            'document_id' => $document->id,
            'document_title' => $document->title
        ]);

        StartGenerateDocument::dispatch($document)->onQueue('document_creates');
    }

    /**
     * Запустить полную генерацию документа
     *
     * @param Document $document
     * @param TransitionService|null $transitionService
     * @return void
     * @throws \Exception если уже есть активное задание
     */
    public function startFullGeneration(Document $document, TransitionService $transitionService = null): void
    {
        $startTime = microtime(true);
        
        Log::channel('queue_operations')->info('🚀 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Начало процесса', [
            'event' => 'start_full_generation_begin',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'document_title' => $document->title,
            'current_status' => $document->status->value,
            'has_transition_service' => $transitionService !== null,
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
        
        // Проверяем статус документа перед началом генерации
        if (in_array($document->status, [DocumentStatus::FULL_GENERATING, DocumentStatus::FULL_GENERATED])) {
            Log::channel('queue_operations')->warning('🚨 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Отклонен по статусу документа', [
                'event' => 'start_full_generation_rejected_status',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'current_status' => $document->status->value,
                'rejected_statuses' => [DocumentStatus::FULL_GENERATING->value, DocumentStatus::FULL_GENERATED->value],
                'process_id' => getmypid()
            ]);
            throw new \Exception('Документ уже генерируется или полностью готов (статус: ' . $document->status->value . ')');
        }
        
        Log::channel('queue_operations')->info('✅ ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Статус документа проверен', [
            'event' => 'start_full_generation_status_ok',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'current_status' => $document->status->value,
            'process_id' => getmypid()
        ]);
        
        // Проверяем активные задачи через hasActiveJob
        $hasActiveJobResult = $this->hasActiveJob($document);
        
        Log::channel('queue_operations')->info('🔍 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Проверка активных задач (hasActiveJob)', [
            'event' => 'start_full_generation_check_active_jobs',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'has_active_job' => $hasActiveJobResult,
            'process_id' => getmypid()
        ]);
        
        if ($hasActiveJobResult) {
            Log::channel('queue_operations')->warning('🚨 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Отклонен - найдена активная задача', [
                'event' => 'start_full_generation_rejected_active_job',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'process_id' => getmypid()
            ]);
            throw new \Exception('Для этого документа уже запущена задача генерации');
        }
        
        // Дополнительная проверка конкретно для StartFullGenerateDocument
        $activeFullGenerationJobs = DB::table('jobs')
            ->where('payload', 'like', '%"document_id":' . $document->id . '%')
            ->where('payload', 'like', '%StartFullGenerateDocument%')
            ->count();
            
        Log::channel('queue_operations')->info('🔍 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Дополнительная проверка StartFullGenerateDocument', [
            'event' => 'start_full_generation_check_specific_jobs',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'active_full_generation_jobs' => $activeFullGenerationJobs,
            'process_id' => getmypid()
        ]);
            
        if ($activeFullGenerationJobs > 0) {
            Log::channel('queue_operations')->warning('🚨 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Отклонен - найдены активные задачи StartFullGenerateDocument', [
                'event' => 'start_full_generation_rejected_specific_jobs',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'active_jobs_count' => $activeFullGenerationJobs,
                'process_id' => getmypid()
            ]);
            throw new \Exception('Для этого документа уже запущена задача полной генерации (найдено активных задач: ' . $activeFullGenerationJobs . ')');
        }

        if ($document->status !== DocumentStatus::FULL_GENERATION_FAILED && $transitionService) {
            $user = $document->user;
            $amount = OrderService::FULL_GENERATION_PRICE;

            $transitionService->debitUser(
                $user,
                $amount,
                "Оплата за полную генерацию документа #{$document->id}"
            );

            Log::info('Списаны средства за полную генерацию документа', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => $document->status->value
            ]);
        } else {
            Log::info('Средства не списаны за полную генерацию документа', [
                'document_id' => $document->id,
                'status' => $document->status->value,
                'reason' => $document->status === DocumentStatus::FULL_GENERATION_FAILED 
                    ? 'Повторная генерация после ошибки (бесплатно)' 
                    : 'TransitionService не передан'
            ]);
        }

        // Обновляем статус документа на full_generating
        Log::channel('queue_operations')->info('📝 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Обновление статуса документа', [
            'event' => 'start_full_generation_update_status',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'old_status' => $document->status->value,
            'new_status' => DocumentStatus::FULL_GENERATING->value,
            'process_id' => getmypid()
        ]);
        
        $document->update(['status' => DocumentStatus::FULL_GENERATING]);

        Log::channel('queue_operations')->info('✅ ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Статус документа обновлен', [
            'event' => 'start_full_generation_status_updated',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'current_status' => $document->status->value,
            'process_id' => getmypid()
        ]);

        Log::info('Запуск полной генерации документа', [
            'document_id' => $document->id,
            'document_title' => $document->title
        ]);

        Log::channel('queue_operations')->info('🎯 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Добавление задачи в очередь', [
            'event' => 'start_full_generation_dispatch_job',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'queue_name' => 'document_creates',
            'job_class' => 'StartFullGenerateDocument',
            'process_id' => getmypid()
        ]);

        StartFullGenerateDocument::dispatch($document)->onQueue('document_creates');
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::channel('queue_operations')->info('🎉 ЗАПУСК ПОЛНОЙ ГЕНЕРАЦИИ: Задача успешно добавлена в очередь', [
            'event' => 'start_full_generation_job_dispatched',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'execution_time_ms' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Безопасный запуск базовой генерации (без выброса исключения)
     *
     * @param Document $document
     * @return array ['success' => bool, 'message' => string]
     */
    public function safeStartBaseGeneration(Document $document): array
    {
        try {
            if ($this->hasActiveJob($document)) {
                return [
                    'success' => false,
                    'message' => 'Для этого документа уже запущена задача генерации'
                ];
            }

            $this->startBaseGeneration($document);

            return [
                'success' => true,
                'message' => 'Генерация документа успешно запущена'
            ];
        } catch (\Exception $e) {
            Log::error('Ошибка при запуске базовой генерации', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при запуске генерации: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Безопасный запуск полной генерации (без выброса исключения)
     *
     * @param Document $document
     * @param TransitionService|null $transitionService
     * @return array ['success' => bool, 'message' => string]
     */
    public function safeStartFullGeneration(Document $document, TransitionService $transitionService = null): array
    {
        try {
            if ($this->hasActiveJob($document)) {
                return [
                    'success' => false,
                    'message' => 'Для этого документа уже запущена задача генерации'
                ];
            }

            $this->startFullGeneration($document, $transitionService);

            return [
                'success' => true,
                'message' => 'Полная генерация документа успешно запущена'
            ];
        } catch (\Exception $e) {
            Log::error('Ошибка при запуске полной генерации', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при запуске генерации: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Проверяет наличие активной задачи генерации для документа
     *
     * @param Document $document
     * @return bool
     */
    public function hasActiveJob(Document $document): bool
    {
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Проверяем наличие активной задачи в очереди через кэш
        $hasActiveJob = Cache::remember(
            'document_has_active_job_' . $document->id,
            now()->addSeconds(5),
            function () use ($documentIdPattern) {
                return DB::table('jobs')
                    ->where('payload', 'like', $documentIdPattern)
                    ->where(function ($q) {
                        foreach (self::JOB_TYPES as $type) {
                            $q->orWhere('payload', 'like', '%' . $type . '%');
                        }
                    })
                    ->exists();
            }
        );

        if ($hasActiveJob) {
            return true;
        }

        // Проверяем failed jobs
        return DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) {
                foreach (self::JOB_TYPES as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->exists();
    }

    /**
     * Удаляет все задания генерации для документа
     *
     * @param Document $document
     * @return array Количество удаленных заданий ['active' => int, 'failed' => int]
     */
    public function deleteJobs(Document $document): array
    {
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Удаляем активные задания
        $activeJobsDeleted = DB::table('jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) {
                foreach (self::JOB_TYPES as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->delete();

        // Удаляем неудачные задания
        $failedJobsDeleted = DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) {
                foreach (self::JOB_TYPES as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->delete();

        // Очищаем кэш проверки активных заданий
        Cache::forget('document_has_active_job_' . $document->id);

        return [
            'active' => $activeJobsDeleted,
            'failed' => $failedJobsDeleted
        ];
    }

    /**
     * Получить статус job документа
     *
     * @param Document $document
     * @return array
     */
    public function getJobStatus(Document $document): array
    {
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Получаем информацию о job из базы данных
        $job = DB::table('jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) {
                foreach (self::JOB_TYPES as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->first();

        // Проверяем failed jobs
        $failedJob = DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) {
                foreach (self::JOB_TYPES as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->first();

        if ($failedJob) {
            return [
                'status' => 'failed',
                'message' => 'Задача завершилась с ошибкой',
                'error' => json_decode($failedJob->exception, true),
                'failed_at' => $failedJob->failed_at
            ];
        }

        if ($job) {
            return [
                'status' => 'processing',
                'message' => 'Задача выполняется',
                'attempts' => $job->attempts,
                'created_at' => $job->created_at,
                'available_at' => $job->available_at
            ];
        }

        return [
            'status' => 'not_found',
            'message' => 'Задача не найдена в очереди'
        ];
    }
} 