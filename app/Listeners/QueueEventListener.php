<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Events\JobTimedOut;
use Illuminate\Support\Facades\Log;

class QueueEventListener
{
    /**
     * Обработка события постановки задачи в очередь
     */
    public function handleJobQueued(JobQueued $event)
    {
        $payload = $this->extractJobPayload($event->payload);
        
        Log::channel('queue_operations')->info('🔄 JOB QUEUED', [
            'event' => 'job_queued',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName,
            'queue' => $event->queue,
            'job_id' => $event->id,
            'job_class' => $payload['job_class'] ?? 'unknown',
            'document_id' => $payload['document_id'] ?? null,
            'payload_size' => strlen($event->payload),
            'delay' => $event->delay,
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события начала выполнения задачи
     */
    public function handleJobProcessing(JobProcessing $event)
    {
        $payload = $this->extractJobPayload($event->job->payload());
        
        Log::channel('queue_operations')->info('▶️ JOB PROCESSING STARTED', [
            'event' => 'job_processing_started',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job_id' => $event->job->getJobId(),
            'job_class' => $payload['job_class'] ?? 'unknown',
            'document_id' => $payload['document_id'] ?? null,
            'attempts' => $event->job->attempts(),
            'max_tries' => $payload['max_tries'] ?? 'unlimited',
            'timeout' => $payload['timeout'] ?? 'default',
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события успешного завершения задачи
     */
    public function handleJobProcessed(JobProcessed $event)
    {
        $payload = $this->extractJobPayload($event->job->payload());
        
        Log::channel('queue_operations')->info('✅ JOB PROCESSED SUCCESSFULLY', [
            'event' => 'job_processed_successfully',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job_id' => $event->job->getJobId(),
            'job_class' => $payload['job_class'] ?? 'unknown',
            'document_id' => $payload['document_id'] ?? null,
            'attempts' => $event->job->attempts(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события неудачного выполнения задачи
     */
    public function handleJobFailed(JobFailed $event)
    {
        $payload = $this->extractJobPayload($event->job->payload());
        
        Log::channel('queue_operations')->error('❌ JOB FAILED', [
            'event' => 'job_failed',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job_id' => $event->job->getJobId(),
            'job_class' => $payload['job_class'] ?? 'unknown',
            'document_id' => $payload['document_id'] ?? null,
            'attempts' => $event->job->attempts(),
            'max_tries' => $payload['max_tries'] ?? 'unlimited',
            'exception_class' => get_class($event->exception),
            'exception_message' => $event->exception->getMessage(),
            'exception_file' => $event->exception->getFile(),
            'exception_line' => $event->exception->getLine(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события исключения в задаче
     */
    public function handleJobExceptionOccurred(JobExceptionOccurred $event)
    {
        $payload = $this->extractJobPayload($event->job->payload());
        
        Log::channel('queue_operations')->warning('⚠️ JOB EXCEPTION OCCURRED', [
            'event' => 'job_exception_occurred',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job_id' => $event->job->getJobId(),
            'job_class' => $payload['job_class'] ?? 'unknown',
            'document_id' => $payload['document_id'] ?? null,
            'attempts' => $event->job->attempts(),
            'exception_class' => get_class($event->exception),
            'exception_message' => $event->exception->getMessage(),
            'exception_file' => $event->exception->getFile(),
            'exception_line' => $event->exception->getLine(),
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события повторного запуска задачи
     */
    public function handleJobRetryRequested(JobRetryRequested $event)
    {
        Log::channel('queue_operations')->warning('🔄 JOB RETRY REQUESTED', [
            'event' => 'job_retry_requested',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName ?? 'unknown',
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Обработка события таймаута задачи
     */
    public function handleJobTimedOut(JobTimedOut $event)
    {
        Log::channel('queue_operations')->error('⏰ JOB TIMED OUT', [
            'event' => 'job_timed_out',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'connection' => $event->connectionName ?? 'unknown',
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);
    }

    /**
     * Извлекает полезную информацию из payload задачи
     */
    private function extractJobPayload($payload): array
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }
        
        $result = [
            'job_class' => $payload['displayName'] ?? $payload['job'] ?? 'unknown'
        ];
        
        // Пытаемся извлечь document_id из данных задачи
        if (isset($payload['data']['document']['id'])) {
            $result['document_id'] = $payload['data']['document']['id'];
        } elseif (isset($payload['data']['document_id'])) {
            $result['document_id'] = $payload['data']['document_id'];
        }
        
        // Извлекаем настройки задачи
        if (isset($payload['maxTries'])) {
            $result['max_tries'] = $payload['maxTries'];
        }
        
        if (isset($payload['timeout'])) {
            $result['timeout'] = $payload['timeout'];
        }
        
        return $result;
    }
} 