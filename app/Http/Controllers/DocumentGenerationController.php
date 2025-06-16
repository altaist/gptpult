<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Jobs\StartFullGenerateDocument;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DocumentGenerationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Запустить полную генерацию документа
     */
    public function startFullGeneration(Document $document)
    {
        $this->authorize('update', $document);

        // Проверяем, можно ли запустить полную генерацию
        if (!$document->status->canStartFullGeneration()) {
            return response()->json([
                'message' => 'Документ не готов к полной генерации',
                'current_status' => $document->status->value,
                'required_status' => DocumentStatus::PRE_GENERATED->value
            ], 422);
        }

        try {
            // Запускаем Job для полной генерации
            StartFullGenerateDocument::dispatch($document);

            return response()->json([
                'message' => 'Полная генерация документа запущена',
                'document_id' => $document->id,
                'status' => 'full_generating'
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
            'is_generating' => $statusEnum->isGenerating(),
            'can_start_full_generation' => $statusEnum->canStartFullGeneration(),
            'is_fully_generated' => $statusEnum->isFullyGenerated(),
            'progress' => [
                'has_basic_structure' => !empty($structure['contents']) && !empty($structure['objectives']),
                'has_detailed_contents' => !empty($structure['detailed_contents']),
                'has_introduction' => !empty($structure['introduction']),
                'has_conclusion' => !empty($structure['conclusion']),
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
        $totalPoints = 10;

        // Базовая структура (40% от общего)
        if (!empty($structure['contents'])) $completionPoints += 2;
        if (!empty($structure['objectives'])) $completionPoints += 2;

        // Полная генерация (60% от общего)
        if (!empty($structure['detailed_contents'])) $completionPoints += 3;
        if (!empty($structure['introduction'])) $completionPoints += 1.5;
        if (!empty($structure['conclusion'])) $completionPoints += 1.5;

        // Бонус за финальные статусы
        if ($status === DocumentStatus::FULL_GENERATED) {
            $completionPoints = $totalPoints; // 100%
        } elseif ($status === DocumentStatus::PRE_GENERATED) {
            $completionPoints = min($completionPoints, 4); // Максимум 40% без полной генерации
        }

        return (int) round(($completionPoints / $totalPoints) * 100);
    }
} 