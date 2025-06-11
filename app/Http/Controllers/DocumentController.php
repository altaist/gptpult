<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
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

        return Inertia::render('documents/ShowDocument', [
            'document' => $document->load('documentType')
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
            'structure' => $structure
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
        ]);

        // Создаем документ через фабрику
        $document = Document::factory()->create([
            'user_id' => Auth::id(),
            'document_type_id' => $validated['document_type_id'],
            'title' => $validated['topic'],
        ]);

        // Обновляем topic в структуре
        $structure = $document->structure;
        $structure['topic'] = $validated['topic'];
        $document->structure = $structure;
        $document->save();

        return response()->json([
            'message' => 'Документ успешно создан',
            'document' => $document
        ], 201);
    }
} 