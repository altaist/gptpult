<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;

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
        $this->authorize('update', $document);

        // Проверяем, можно ли запустить полную генерацию (включая проверку ссылок)
        if (!$document->status->canStartFullGenerationWithReferences($document)) {
            $structure = $document->structure ?? [];
            $hasReferences = !empty($structure['references']);
            
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
            // Используем DocumentJobService для запуска полной генерации с автоматическим списанием
            $this->documentJobService->startFullGeneration($document, $this->transitionService);

            return response()->json([
                'message' => 'Полная генерация документа запущена',
                'document_id' => $document->id,
                'status' => DocumentStatus::FULL_GENERATING->value
            ]);

        } catch (\Exception $e) {
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