<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Document;
use App\Services\Gpt\OpenAiService;
use App\Services\Gpt\GptServiceFactory;
use Carbon\Carbon;

class MultiDocumentWorker extends Command
{
    protected $signature = 'debug:multi-doc-worker 
                          {--worker-id=1 : Worker ID} 
                          {--document-ids= : Comma-separated document IDs}
                          {--iterations=5 : Number of iterations}
                          {--delay=1 : Delay between iterations in seconds}
                          {--test-id= : Test ID for logging}';

    protected $description = 'Worker for processing multiple documents in parallel testing';

    private $openAIService;
    private $workerId;
    private $testId;
    private $stats = [
        'total_attempts' => 0,
        'successful_adds' => 0,
        'failed_adds' => 0,
        'successful_runs' => 0,
        'failed_runs' => 0,
        'active_run_errors' => 0,
        'documents_processed' => [],
        'errors' => []
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->workerId = $this->option('worker-id');
        $this->testId = $this->option('test-id');
        $documentIds = explode(',', $this->option('document-ids'));
        $iterations = (int) $this->option('iterations');
        $delay = (int) $this->option('delay');

        // Инициализируем OpenAI сервис
        $factory = app(GptServiceFactory::class);
        $this->openAIService = $factory->make('openai');

        $this->logEvent('Multi-document worker started', [
            'worker_id' => $this->workerId,
            'document_ids' => $documentIds,
            'iterations' => $iterations,
            'delay' => $delay
        ]);

        $startTime = microtime(true);

        try {
            // Обработка каждого документа
            foreach ($documentIds as $documentId) {
                $documentId = trim($documentId);
                if (empty($documentId)) continue;

                $this->processDocument($documentId, $iterations, $delay);
            }

        } catch (\Exception $e) {
            $this->logEvent('Multi-document worker error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        $totalTime = microtime(true) - $startTime;
        $this->stats['total_execution_time'] = $totalTime;

        $this->logEvent('Multi-document worker final stats', $this->stats);
        $this->logEvent('Multi-document worker completed successfully', []);

        return 0;
    }

    private function processDocument(string $documentId, int $iterations, int $delay): void
    {
        $this->logEvent('Processing document started', [
            'document_id' => $documentId
        ]);

        $document = Document::find($documentId);
        if (!$document) {
            $this->logEvent('Document not found', [
                'document_id' => $documentId
            ]);
            return;
        }

        // Инициализация thread_id если не существует
        if (!$document->thread_id) {
            $this->initializeDocumentThread($document);
        }

        $this->stats['documents_processed'][] = $documentId;

        // Выполнение итераций для документа
        for ($i = 1; $i <= $iterations; $i++) {
            $this->processIteration($document, $i);
            
            if ($delay > 0 && $i < $iterations) {
                sleep($delay);
            }
        }

        $this->logEvent('Processing document completed', [
            'document_id' => $documentId,
            'iterations_completed' => $iterations
        ]);
    }

    private function initializeDocumentThread(Document $document): void
    {
        try {
            $this->logEvent('Initializing thread for document', [
                'document_id' => $document->id
            ]);

            $thread = $this->openAIService->createThread();
            $document->thread_id = $thread['id'];
            $document->save();

            $this->logEvent('Thread initialized successfully', [
                'document_id' => $document->id,
                'thread_id' => $thread['id']
            ]);

        } catch (\Exception $e) {
            $this->logEvent('Failed to initialize thread', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function processIteration(Document $document, int $iteration): void
    {
        $this->stats['total_attempts']++;

        $this->logEvent('Processing iteration started', [
            'document_id' => $document->id,
            'iteration' => $iteration,
            'thread_id' => $document->thread_id
        ]);

        try {
            // Добавление сообщения
            $message = $this->generateTestMessage($iteration);
            $this->addMessage($document, $message, $iteration);

            // Создание и выполнение run
            $this->createAndRunAssistant($document, $iteration);

        } catch (\Exception $e) {
            $this->logEvent('Iteration failed', [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'error' => $e->getMessage(),
                'error_type' => $this->classifyError($e->getMessage())
            ]);

            $this->stats['errors'][] = [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'error' => $e->getMessage(),
                'type' => $this->classifyError($e->getMessage())
            ];
        }
    }

    private function addMessage(Document $document, string $message, int $iteration): void
    {
        $timestampBefore = microtime(true);

        $this->logEvent('Attempting to add message', [
            'document_id' => $document->id,
            'iteration' => $iteration,
            'thread_id' => $document->thread_id,
            'message_length' => strlen($message)
        ]);

        try {
            $messageResponse = $this->openAIService->addMessageToThread($document->thread_id, $message);
            $addTime = microtime(true) - $timestampBefore;

            $this->logEvent('Message added successfully', [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'thread_id' => $document->thread_id,
                'message_id' => $messageResponse['id'],
                'add_time' => $addTime . 's'
            ]);

            $this->stats['successful_adds']++;

        } catch (\Exception $e) {
            $addTime = microtime(true) - $timestampBefore;
            $isActiveRunError = $this->isActiveRunError($e->getMessage());

            $this->logEvent('Failed to add message', [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'thread_id' => $document->thread_id,
                'error' => $e->getMessage(),
                'add_time' => $addTime . 's',
                'is_active_run_error' => $isActiveRunError
            ]);

            $this->stats['failed_adds']++;
            if ($isActiveRunError) {
                $this->stats['active_run_errors']++;
            }

            throw $e;
        }
    }

    private function createAndRunAssistant(Document $document, int $iteration): void
    {
        $timestampBefore = microtime(true);

        $this->logEvent('Creating run', [
            'document_id' => $document->id,
            'iteration' => $iteration,
            'thread_id' => $document->thread_id,
            'assistant_id' => 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju'
        ]);

        try {
            $run = $this->openAIService->createRun($document->thread_id, 'asst_8FBCbxGFVWfhwnGLHyo7T3Ju');
            $createTime = microtime(true) - $timestampBefore;

            $this->logEvent('Run created', [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'thread_id' => $document->thread_id,
                'run_id' => $run['id'],
                'status' => $run['status'],
                'create_time' => $createTime . 's'
            ]);

            $this->stats['successful_runs']++;

            // Ожидание завершения run
            $this->waitForRunCompletion($document, $run, $iteration);

        } catch (\Exception $e) {
            $createTime = microtime(true) - $timestampBefore;

            $this->logEvent('Failed to create run', [
                'document_id' => $document->id,
                'iteration' => $iteration,
                'thread_id' => $document->thread_id,
                'error' => $e->getMessage(),
                'create_time' => $createTime . 's'
            ]);

            $this->stats['failed_runs']++;
            throw $e;
        }
    }

    private function waitForRunCompletion(Document $document, $run, int $iteration): void
    {
        $maxAttempts = 30;
        $attempt = 0;
        $waitTime = 1;

        while ($attempt < $maxAttempts) {
            try {
                $runStatus = $this->openAIService->getRunStatus($document->thread_id, $run['id']);
                
                $this->logEvent('Run status check', [
                    'document_id' => $document->id,
                    'iteration' => $iteration,
                    'thread_id' => $document->thread_id,
                    'run_id' => $run['id'],
                    'status' => $runStatus['status'],
                    'attempt' => $attempt + 1
                ]);

                if (in_array($runStatus['status'], ['completed', 'failed', 'cancelled', 'expired'])) {
                    $this->logEvent('Run completed', [
                        'document_id' => $document->id,
                        'iteration' => $iteration,
                        'thread_id' => $document->thread_id,
                        'run_id' => $run['id'],
                        'final_status' => $runStatus['status'],
                        'total_wait_time' => ($attempt + 1) * $waitTime . 's'
                    ]);
                    return;
                }

                $attempt++;
                sleep($waitTime);

            } catch (\Exception $e) {
                $this->logEvent('Run status check failed', [
                    'document_id' => $document->id,
                    'iteration' => $iteration,
                    'thread_id' => $document->thread_id,
                    'run_id' => $run['id'],
                    'attempt' => $attempt + 1,
                    'error' => $e->getMessage()
                ]);
                
                $attempt++;
                sleep($waitTime);
            }
        }

        $this->logEvent('Run completion timeout', [
            'document_id' => $document->id,
            'iteration' => $iteration,
            'thread_id' => $document->thread_id,
            'run_id' => $run['id'],
            'max_attempts' => $maxAttempts
        ]);
    }

    private function generateTestMessage(int $iteration): string
    {
        $messages = [
            "Тестовое сообщение для итерации #{$iteration}. Время: " . now()->toDateTimeString(),
            "Проверка многопоточности. Итерация #{$iteration}. Воркер: {$this->workerId}",
            "Анализ производительности системы. Шаг #{$iteration}",
            "Тестирование обработки документов. Попытка #{$iteration}",
            "Проверка стабильности API. Запрос #{$iteration}"
        ];

        return $messages[($iteration - 1) % count($messages)];
    }

    private function isActiveRunError(string $error): bool
    {
        return strpos($error, 'while a run') !== false || 
               strpos($error, 'already has an active run') !== false;
    }

    private function classifyError(string $error): string
    {
        if ($this->isActiveRunError($error)) {
            return 'active_run_error';
        }
        
        if (strpos($error, 'timeout') !== false) {
            return 'timeout_error';
        }
        
        if (strpos($error, 'rate limit') !== false) {
            return 'rate_limit_error';
        }
        
        if (strpos($error, 'network') !== false) {
            return 'network_error';
        }
        
        return 'unknown_error';
    }

    private function logEvent(string $event, array $data = []): void
    {
        $logData = array_merge([
            'worker_id' => $this->workerId,
            'worker_type' => 'multi_document',
            'test_id' => $this->testId,
            'event' => $event,
            'timestamp' => now()->toDateTimeString(),
            'process_id' => getmypid(),
            'memory_usage' => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage()
        ], $data);

        Log::channel('debug_generation')->info("MultiDocWorker #{$this->workerId}: {$event}", $logData);
    }
} 