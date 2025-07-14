<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Jobs\TestQueueJob;
use App\Jobs\StartGenerateDocument;
use App\Jobs\StartFullGenerateDocument;
use App\Jobs\AsyncGenerateDocument;
use App\Jobs\BatchGenerateDocuments;
use App\Models\Document;
use App\Models\User;
use App\Models\DocumentType;
use App\Services\Queue\WorkerManagementService;

class AdminQueueController extends Controller
{
    protected WorkerManagementService $workerService;

    public function __construct(WorkerManagementService $workerService)
    {
        $this->workerService = $workerService;
    }

    /**
     * Главная страница управления очередями
     */
    public function index()
    {
        $queueStats = $this->getQueueStats();
        $workerStats = $this->workerService->getRunningWorkers();
        $recentJobs = $this->getRecentJobs();
        $failedJobs = $this->getFailedJobs();

        return Inertia::render('admin/queue/Index', [
            'queueStats' => $queueStats,
            'workerStats' => $workerStats,
            'recentJobs' => $recentJobs,
            'failedJobs' => $failedJobs,
        ]);
    }

    /**
     * Получить статистику очередей
     */
    public function getQueueStats()
    {
        $stats = [];
        
        // Основная очередь документов
        $stats['document_creates'] = [
            'name' => 'document_creates',
            'display_name' => 'Генерация документов',
            'pending' => DB::table('jobs')->where('queue', 'document_creates')->count(),
            'processing' => $this->getProcessingJobsCount('document_creates'),
            'failed' => DB::table('failed_jobs')->where('queue', 'document_creates')->count(),
        ];

        // Общая очередь
        $stats['default'] = [
            'name' => 'default',
            'display_name' => 'Основная очередь',
            'pending' => DB::table('jobs')->where('queue', 'default')->count(),
            'processing' => $this->getProcessingJobsCount('default'),
            'failed' => DB::table('failed_jobs')->where('queue', 'default')->count(),
        ];

        return $stats;
    }

    /**
     * Получить статистику воркеров
     */
    public function getWorkerStats()
    {
        return $this->workerService->getRunningWorkers();
    }

    /**
     * Получить последние задачи
     */
    public function getRecentJobs($limit = 10)
    {
        return DB::table('jobs')
            ->select([
                'id',
                'queue',
                'payload',
                'attempts',
                'created_at',
                'available_at'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'job_class' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                    'document_id' => $this->extractDocumentId($payload),
                    'attempts' => $job->attempts,
                    'created_at' => Carbon::createFromTimestamp($job->created_at)->format('Y-m-d H:i:s'),
                    'available_at' => Carbon::createFromTimestamp($job->available_at)->format('Y-m-d H:i:s'),
                ];
            });
    }

    /**
     * Получить проваленные задачи
     */
    public function getFailedJobs($limit = 10)
    {
        return DB::table('failed_jobs')
            ->select([
                'id',
                'uuid',
                'queue',
                'payload',
                'exception',
                'failed_at'
            ])
            ->orderBy('failed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'job_class' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                    'document_id' => $this->extractDocumentId($payload),
                    'failed_at' => $job->failed_at,
                    'exception' => $this->formatException($job->exception),
                ];
            });
    }

    /**
     * Запустить воркер
     */
    public function startWorker(Request $request)
    {
        $validated = $request->validate([
            'queue' => 'required|string|in:default,document_creates',
            'timeout' => 'nullable|integer|min:60|max:3600',
        ]);

        $result = $this->workerService->startWorker(
            $validated['queue'],
            $validated['timeout'] ?? 600
        );

        return response()->json($result);
    }

    /**
     * Остановить воркер
     */
    public function stopWorker(Request $request)
    {
        $validated = $request->validate([
            'pid' => 'required|integer',
        ]);

        $result = $this->workerService->stopWorker($validated['pid']);

        return response()->json($result);
    }

    /**
     * Добавить тестовую задачу в очередь
     */
    public function addTestJob(Request $request)
    {
        $validated = $request->validate([
            'queue' => 'required|string|in:default,document_creates',
        ]);

        try {
            TestQueueJob::dispatch()->onQueue($validated['queue']);
            
            return response()->json([
                'success' => true,
                'message' => "Тестовая задача добавлена в очередь {$validated['queue']}"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка добавления задачи: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить задачу из очереди
     */
    public function deleteJob(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|integer',
        ]);

        try {
            $deleted = DB::table('jobs')->where('id', $validated['job_id'])->delete();
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Задача удалена из очереди'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Задача не найдена'
                ], 404);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления задачи: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Повторить проваленную задачу
     */
    public function retryFailedJob(Request $request)
    {
        $validated = $request->validate([
            'job_uuid' => 'required|string',
        ]);

        try {
            Artisan::call('queue:retry', ['id' => $validated['job_uuid']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Проваленная задача поставлена на повтор'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка повтора задачи: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Очистить проваленные задачи
     */
    public function clearFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            
            return response()->json([
                'success' => true,
                'message' => 'Все проваленные задачи удалены'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки проваленных задач: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создать job для генерации документа
     */
    public function createDocumentJob(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'job_type' => 'required|string|in:base_generation,full_generation,async_generation',
            'queue' => 'required|string|in:default,document_creates',
        ]);

        try {
            $document = Document::findOrFail($validated['document_id']);
            
            switch ($validated['job_type']) {
                case 'base_generation':
                    StartGenerateDocument::dispatch($document)->onQueue($validated['queue']);
                    $message = "Job базовой генерации создан для документа {$document->id}";
                    break;
                    
                case 'full_generation':
                    StartFullGenerateDocument::dispatch($document)->onQueue($validated['queue']);
                    $message = "Job полной генерации создан для документа {$document->id}";
                    break;
                    
                case 'async_generation':
                    AsyncGenerateDocument::dispatch($document)->onQueue($validated['queue']);
                    $message = "Job асинхронной генерации создан для документа {$document->id}";
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Создать batch job для нескольких документов
     */
    public function createBatchJob(Request $request)
    {
        $validated = $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id',
            'queue' => 'required|string|in:default,document_creates',
        ]);

        try {
            BatchGenerateDocuments::dispatch($validated['document_ids'])->onQueue($validated['queue']);
            
            return response()->json([
                'success' => true,
                'message' => "Batch job создан для " . count($validated['document_ids']) . " документов"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания batch job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить список документов для создания job
     */
    public function getDocumentsForJob()
    {
        $documents = Document::with(['user:id,name', 'documentType:id,title'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'status', 'user_id', 'document_type_id', 'created_at']);

        return response()->json($documents);
    }

    /**
     * Очистить все очереди
     */
    public function clearAllQueues()
    {
        try {
            $deletedJobs = DB::table('jobs')->delete();
            $this->clearFailedJobs();
            
            return response()->json([
                'success' => true,
                'message' => "Удалено {$deletedJobs} заданий из всех очередей"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки очередей: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Остановить все воркеры
     */
    public function stopAllWorkers()
    {
        try {
            $workers = $this->workerService->getRunningWorkers();
            $results = [];
            
            foreach ($workers as $worker) {
                $result = $this->workerService->stopWorker($worker['pid']);
                $results[] = $result;
            }
            
            $successful = count(array_filter($results, fn($r) => $r['success']));
            
            return response()->json([
                'success' => $successful > 0,
                'message' => "Остановлено {$successful} из " . count($workers) . " воркеров",
                'details' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка остановки воркеров: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Перезапустить воркеры для всех очередей
     */
    public function restartAllWorkers()
    {
        try {
            // Останавливаем все воркеры
            $stopResult = $this->stopAllWorkers();
            
            // Ждем завершения
            sleep(2);
            
            // Запускаем воркеры для основных очередей
            $startResults = [];
            
            $queues = ['default', 'document_creates'];
            foreach ($queues as $queue) {
                $startResult = $this->workerService->startWorker($queue);
                $startResults[] = $startResult;
            }
            
            $successful = count(array_filter($startResults, fn($r) => $r['success']));
            
            return response()->json([
                'success' => $successful > 0,
                'message' => "Перезапущено воркеров для {$successful} очередей",
                'stop_result' => $stopResult,
                'start_results' => $startResults
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка перезапуска воркеров: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить обновленные данные для dashboard
     */
    public function getDashboardData()
    {
        return response()->json([
            'queueStats' => $this->getQueueStats(),
            'workerStats' => $this->workerService->getRunningWorkers(),
            'recentJobs' => $this->getRecentJobs(),
            'failedJobs' => $this->getFailedJobs(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Вспомогательные методы
     */
    
    private function getProcessingJobsCount($queue)
    {
        // Примерная оценка обрабатывающихся задач
        return Cache::get("queue_processing_{$queue}", 0);
    }

    private function extractDocumentId($payload)
    {
        if (isset($payload['data']['document']['id'])) {
            return $payload['data']['document']['id'];
        }
        
        if (isset($payload['data']['document_id'])) {
            return $payload['data']['document_id'];
        }
        
        return null;
    }

    private function formatException($exception)
    {
        $lines = explode("\n", $exception);
        return $lines[0] ?? 'Unknown error';
    }
} 