<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DocumentGenerationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DocumentJobService $documentJobService,
        protected TransitionService $transitionService
    ) {}

    /**
     * Запустить полную генерацию документа
     */
    public function startFullGeneration(Document $document)
    {
        $requestStartTime = microtime(true);
        
        $this->authorize('update', $document);

        Log::channel('queue_operations')->info('🌐 API ЗАПРОС: Запуск полной генерации', [
            'event' => 'api_start_full_generation_request',
            'timestamp' => now()->format('Y-m-d H:i:s.v'),
            'document_id' => $document->id,
            'document_title' => $document->title,
            'current_status' => $document->status->value,
            'user_id' => Auth::id(),
            'user_agent' => request()->header('User-Agent'),
            'ip' => request()->ip(),
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'memory_usage' => memory_get_usage(true),
            'process_id' => getmypid()
        ]);

        Log::info('API: Попытка запуска полной генерации', [
            'document_id' => $document->id,
            'current_status' => $document->status->value,
            'user_id' => Auth::id(),
            'user_agent' => request()->header('User-Agent'),
            'ip' => request()->ip()
        ]);

        // Проверяем, можно ли запустить полную генерацию (включая проверку ссылок)
        if (!$document->status->canStartFullGenerationWithReferences($document)) {
            $structure = $document->structure ?? [];
            $hasReferences = !empty($structure['references']);
            
            Log::warning('API: Отклонен запуск полной генерации', [
                'document_id' => $document->id,
                'current_status' => $document->status->value,
                'has_references' => $hasReferences,
                'reason' => $hasReferences ? 'Статус не позволяет' : 'Нет ссылок'
            ]);
            
            return response()->json([
                'message' => $hasReferences 
                    ? 'Документ не готов к полной генерации' 
                    : 'Документ не готов к полной генерации: ожидается завершение генерации ссылок',
                'current_status' => $document->status->value,
                'required_status' => DocumentStatus::PRE_GENERATED->value,
                'has_references' => $hasReferences,
                'references_required' => true
            ], 422);
        }

        try {
            Log::channel('queue_operations')->info('🔄 API ЗАПРОС: Вызов DocumentJobService.startFullGeneration', [
                'event' => 'api_call_document_job_service',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'process_id' => getmypid()
            ]);
            
            // Используем DocumentJobService для запуска полной генерации с автоматическим списанием
            $this->documentJobService->startFullGeneration($document, $this->transitionService);

            Log::channel('queue_operations')->info('✅ API ЗАПРОС: DocumentJobService.startFullGeneration выполнен успешно', [
                'event' => 'api_document_job_service_success',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'process_id' => getmypid()
            ]);

            Log::info('API: Полная генерация успешно запущена', [
                'document_id' => $document->id,
                'user_id' => Auth::id()
            ]);

            $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2);
            
            Log::channel('queue_operations')->info('🎉 API ОТВЕТ: Полная генерация успешно запущена', [
                'event' => 'api_start_full_generation_success',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'response_time_ms' => $responseTime,
                'memory_usage' => memory_get_usage(true),
                'process_id' => getmypid()
            ]);

            return response()->json([
                'message' => 'Полная генерация документа запущена',
                'document_id' => $document->id,
                'status' => DocumentStatus::FULL_GENERATING->value
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $requestStartTime) * 1000, 2);
            
            Log::channel('queue_operations')->error('❌ API ОШИБКА: Не удалось запустить полную генерацию', [
                'event' => 'api_start_full_generation_error',
                'timestamp' => now()->format('Y-m-d H:i:s.v'),
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'response_time_ms' => $responseTime,
                'memory_usage' => memory_get_usage(true),
                'process_id' => getmypid()
            ]);

            Log::error('API: Ошибка при запуске полной генерации', [
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Ошибка при запуске полной генерации',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить прогресс полной генерации
     */
    public function getGenerationProgress(Document $document)
    {
        $this->authorize('view', $document);

        $structure = $document->structure ?? [];
        $statusEnum = $document->status;

        return response()->json([
            'document_id' => $document->id,
            'status' => $statusEnum->value,
            'status_label' => $statusEnum->getLabel(),
            'status_color' => $statusEnum->getColor(),
            'status_icon' => $statusEnum->getIcon(),
            'is_generating' => $statusEnum->isGenerating(),
            'is_final' => $statusEnum->isFinal(),
            'can_start_full_generation' => $statusEnum->canStartFullGenerationWithReferences($document),
            'is_fully_generated' => $statusEnum->isFullyGenerated(),
            'progress' => [
                'has_basic_structure' => !empty($structure['contents']) && !empty($structure['objectives']),
                'has_detailed_contents' => !empty($structure['detailed_contents']),
                'has_introduction' => !empty($structure['introduction']),
                'has_conclusion' => !empty($structure['conclusion']),
                'has_references' => !empty($structure['references']),
                'completion_percentage' => $this->calculateCompletionPercentage($structure, $statusEnum)
            ]
        ]);
    }

    /**
     * Вычислить процент завершенности документа
     */
    private function calculateCompletionPercentage(array $structure, DocumentStatus $status): int
    {
        $completionPoints = 0;
        $totalPoints = 12; // Увеличиваем общее количество баллов для учета ссылок

        // Базовая структура (30%)
        if (!empty($structure['contents'])) {
            $completionPoints += 2;
        }
        if (!empty($structure['objectives'])) {
            $completionPoints += 1;
        }
        
        // Ссылки (15%)
        if (!empty($structure['references'])) {
            $completionPoints += 2;
        }

        // Полная генерация (55%)
        if (!empty($structure['detailed_contents'])) {
            $completionPoints += 4;
        }
        if (!empty($structure['introduction'])) {
            $completionPoints += 1.5;
        }
        if (!empty($structure['conclusion'])) {
            $completionPoints += 1.5;
        }

        $percentage = min(100, round(($completionPoints / $totalPoints) * 100));
        
        return $percentage;
    }
} 