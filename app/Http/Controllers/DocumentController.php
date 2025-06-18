<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use App\Services\Documents\Files\WordDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    protected DocumentService $documentService;
    protected WordDocumentService $wordDocumentService;

    public function __construct(
        DocumentService $documentService,
        WordDocumentService $wordDocumentService
    ) {
        $this->documentService = $documentService;
        $this->wordDocumentService = $wordDocumentService;
    }

    /**
     * Показать форму создания документа
     */
    public function create()
    {
        $documentTypes = DocumentType::all();
        return view('documents.create', compact('documentTypes'));
    }

    /**
     * Сохранить новый документ
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'topic' => ['required', 'string', 'max:255'],
            'theses' => ['nullable', 'string'],
            'objectives' => ['nullable', 'array'],
            'objectives.*' => ['string', 'max:255'],
            'contents' => ['nullable', 'array'],
            'contents.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics' => ['nullable', 'array'],
            'contents.*.subtopics.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics.*.content' => ['required', 'string'],
            'references' => ['nullable', 'array'],
            'references.*.title' => ['required', 'string', 'max:255'],
            'references.*.author' => ['required', 'string', 'max:255'],
            'references.*.year' => ['required', 'string', 'max:4'],
            'references.*.url' => ['required', 'url', 'max:255'],
            'content' => ['nullable', 'string'],
            'pages_num' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'gpt_settings' => ['nullable', 'array'],
            'gpt_settings.service' => ['nullable', 'string', 'in:openai,anthropic'],
            'gpt_settings.model' => ['nullable', 'string'],
            'gpt_settings.temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'gpt_settings.max_tokens' => ['nullable', 'integer', 'min:1', 'max:8192'],
        ]);

        $structure = [
            'topic' => $validated['topic'],
            'theses' => $validated['theses'] ?? '',
            'objectives' => $validated['objectives'] ?? [],
            'contents' => $validated['contents'] ?? [],
            'references' => $validated['references'] ?? [],
        ];

        $document = $this->documentService->create([
            'user_id' => Auth::id(),
            'document_type_id' => $validated['document_type_id'],
            'title' => $validated['topic'], // Используем тему как заголовок по умолчанию
            'structure' => $structure,
            'content' => $validated['content'] ?? null,
            'pages_num' => $validated['pages_num'] ?? null,
            'gpt_settings' => $validated['gpt_settings'] ?? null,
            'status' => 'draft'
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Документ успешно создан');
    }

    /**
     * Показать документ
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $order = $document->orders()->latest()->first();
        $orderPrice = (float) ($order?->amount ?? \App\Services\Orders\OrderService::DEFAULT_PRICE);
        $balance = $document->user->balance_rub ?? 0;

        return Inertia::render('documents/ShowDocument', [
            'document' => array_merge(
                $document->load('documentType')->toArray(),
                [
                    'status' => $document->status->value,
                    'status_label' => $document->status->getLabel(),
                    'status_color' => $document->status->getColor(),
                    'status_icon' => $document->status->getIcon()
                ]
            ),
            'balance' => $balance,
            'orderPrice' => $orderPrice
        ]);
    }

    /**
     * Показать форму редактирования документа
     */
    public function edit(Document $document)
    {
        $this->authorize('update', $document);
        $documentTypes = DocumentType::all();
        return view('documents.edit', compact('document', 'documentTypes'));
    }

    /**
     * Обновить документ
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'topic' => ['required', 'string', 'max:255'],
            'theses' => ['nullable', 'string'],
            'objectives' => ['nullable', 'array'],
            'objectives.*' => ['string', 'max:255'],
            'contents' => ['nullable', 'array'],
            'contents.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics' => ['nullable', 'array'],
            'contents.*.subtopics.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics.*.content' => ['required', 'string'],
            'references' => ['nullable', 'array'],
            'references.*.title' => ['required', 'string', 'max:255'],
            'references.*.author' => ['required', 'string', 'max:255'],
            'references.*.year' => ['required', 'string', 'max:4'],
            'references.*.url' => ['required', 'url', 'max:255'],
            'content' => ['nullable', 'string'],
            'pages_num' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'gpt_settings' => ['nullable', 'array'],
            'gpt_settings.service' => ['nullable', 'string', 'in:openai,anthropic'],
            'gpt_settings.model' => ['nullable', 'string'],
            'gpt_settings.temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'gpt_settings.max_tokens' => ['nullable', 'integer', 'min:1', 'max:8192'],
        ]);

        $structure = [
            'topic' => $validated['topic'],
            'theses' => $validated['theses'] ?? '',
            'objectives' => $validated['objectives'] ?? [],
            'contents' => $validated['contents'] ?? [],
            'references' => $validated['references'] ?? [],
        ];

        $this->documentService->update($document, [
            'document_type_id' => $validated['document_type_id'],
            'title' => $validated['topic'],
            'structure' => $structure,
            'content' => $validated['content'] ?? null,
            'pages_num' => $validated['pages_num'] ?? null,
            'gpt_settings' => $validated['gpt_settings'] ?? null,
        ]);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Документ успешно обновлен');
    }

    /**
     * Удалить документ
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);
        
        $this->documentService->delete($document);

        return redirect()
            ->route('documents.index')
            ->with('success', 'Документ успешно удален');
    }

    /**
     * Быстрое создание документа с минимальными данными
     */
    public function quickCreate(Request $request)
    {
        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:document_types,id'],
            'topic' => ['required', 'string', 'max:255'],
            'test' => ['nullable', 'boolean'], // Параметр для тестирования с фейковыми данными
        ]);

        // Если передан параметр test, создаем с фейковыми данными из фабрики
        if ($request->boolean('test')) {
            $document = Document::factory()->create([
                'user_id' => Auth::id(),
                'document_type_id' => $validated['document_type_id'],
                'title' => $validated['topic'],
                'status' => 'pre_generating', // Сразу устанавливаем статус генерации
            ]);

            // Обновляем topic в структуре фабрики
            $structure = $document->structure;
            $structure['topic'] = $validated['topic'];
            $document->structure = $structure;
            $document->save();
        } else {
            // Создаем минимальный документ только с переданными данными
            $document = Document::factory()->minimal()->create([
                'user_id' => Auth::id(),
                'document_type_id' => $validated['document_type_id'],
                'title' => $validated['topic'],
                'status' => 'pre_generating', // Сразу устанавливаем статус генерации
            ]);

            // Обновляем topic в структуре документа
            $structure = $document->structure ?? [];
            $structure['topic'] = $validated['topic'];
            $document->structure = $structure;
            $document->save();
        }

        // Запускаем Job для генерации документа
        \App\Jobs\StartGenerateDocument::dispatch($document);

        return response()->json([
            'message' => 'Документ успешно создан',
            'document' => $document,
            'redirect_url' => route('documents.show', ['document' => $document->id, 'autoload' => 1])
        ], 201);
    }

    /**
     * Проверить статус документа
     */
    public function checkStatus(Document $document)
    {
        $this->authorize('view', $document);

        $statusEnum = $document->status;

        return response()->json([
            'document_id' => $document->id,
            'status' => $statusEnum->value,
            'status_label' => $statusEnum->getLabel(),
            'status_color' => $statusEnum->getColor(),
            'status_icon' => $statusEnum->getIcon(),
            'is_final' => $statusEnum->isFinal(),
            'is_generating' => $statusEnum->isGenerating(),
            'can_start_full_generation' => $statusEnum->canStartFullGeneration(),
            'is_fully_generated' => $statusEnum->isFullyGenerated(),
            'title' => $document->title,
            'updated_at' => $document->updated_at,
            'has_contents' => !empty($document->structure['contents']),
            'has_objectives' => !empty($document->structure['objectives']),
            'has_detailed_contents' => !empty($document->structure['detailed_contents']),
            'has_introduction' => !empty($document->structure['introduction']),
            'has_conclusion' => !empty($document->structure['conclusion']),
            'structure_complete' => !empty($document->structure['contents']) && !empty($document->structure['objectives']),
            'document' => $document->load('documentType') // Добавляем полные данные документа
        ]);
    }

    /**
     * Сгенерировать и скачать Word-документ
     */
    public function downloadWord(Document $document)
    {
        $this->authorize('view', $document);

        try {
            $file = $this->wordDocumentService->generate($document);

            return response()->json([
                'message' => 'Документ успешно сгенерирован',
                'url' => $file->getPublicUrl(),
                'filename' => $file->display_name
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при генерации документа: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обновить настройки GPT для документа
     */
    public function updateGptSettings(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'service' => ['nullable', 'string', 'in:openai,anthropic'],
            'model' => ['nullable', 'string'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_tokens' => ['nullable', 'integer', 'min:1', 'max:8192'],
        ]);

        $this->documentService->updateGptSettings($document, $validated);

        return response()->json([
            'message' => 'Настройки GPT успешно обновлены',
            'gpt_settings' => $document->fresh()->gpt_settings
        ]);
    }

    /**
     * Обновить содержание документа
     */
    public function updateContent(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $this->documentService->updateContent($document, $validated['content']);

        return response()->json([
            'message' => 'Содержание документа успешно обновлено'
        ]);
    }

    /**
     * Обновить количество страниц документа
     */
    public function updatePagesNum(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'pages_num' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $this->documentService->updatePagesNum($document, $validated['pages_num']);

        return response()->json([
            'message' => 'Количество страниц успешно обновлено'
        ]);
    }

    /**
     * Обновить тему документа
     */
    public function updateTopic(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
        ]);

        $this->documentService->updateTopic($document, $validated['topic']);

        return response()->json([
            'message' => 'Тема документа успешно обновлена',
            'topic' => $validated['topic']
        ]);
    }

    /**
     * Обновить цели документа
     */
    public function updateObjectives(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'objectives' => ['required', 'array'],
            'objectives.*' => ['string', 'max:255'],
        ]);

        $this->documentService->updateObjectives($document, $validated['objectives']);

        return response()->json([
            'message' => 'Цели документа успешно обновлены',
            'objectives' => $validated['objectives']
        ]);
    }

    /**
     * Обновить тезисы документа
     */
    public function updateTheses(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'theses' => ['required', 'string'],
        ]);

        $this->documentService->updateTheses($document, $validated['theses']);

        return response()->json([
            'message' => 'Тезисы документа успешно обновлены',
            'theses' => $validated['theses']
        ]);
    }

    /**
     * Обновить содержание документа
     */
    public function updateContents(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'contents' => ['required', 'array'],
            'contents.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics' => ['nullable', 'array'],
            'contents.*.subtopics.*.title' => ['required', 'string', 'max:255'],
            'contents.*.subtopics.*.content' => ['nullable', 'string'],
        ]);

        $this->documentService->updateContents($document, $validated['contents']);

        return response()->json([
            'message' => 'Содержание документа успешно обновлено',
            'contents' => $validated['contents']
        ]);
    }
} 