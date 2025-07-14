<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentType;
use App\Enums\DocumentStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AdminDocumentController extends Controller
{
    /**
     * Список всех документов
     */
    public function index(Request $request)
    {
        $query = Document::with(['user', 'documentType']);

        // Поиск по названию или ID
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Фильтр по статусу
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по пользователю
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Фильтр по типу документа
        if ($request->filled('document_type_id')) {
            $query->where('document_type_id', $request->document_type_id);
        }

        $documents = $query->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'status', 'user_id', 'document_type_id']),
            'statuses' => collect(DocumentStatus::cases())->map(fn($status) => [
                'value' => $status->value,
                'label' => $status->getLabel()
            ]),
            'document_types' => DocumentType::all(['id', 'title']),
            'users' => User::all(['id', 'name', 'email'])
        ]);
    }

    /**
     * Показать документ (как в обычном документ контроллере)
     */
    public function show(Document $document)
    {
        $document->load(['user', 'documentType', 'files']);

        return Inertia::render('admin/documents/Show', [
            'document' => $document
        ]);
    }

    /**
     * Показать форму редактирования документа
     */
    public function edit(Document $document)
    {
        $document->load(['user', 'documentType']);

        return Inertia::render('admin/documents/Edit', [
            'document' => $document,
            'statuses' => collect(DocumentStatus::cases())->map(fn($status) => [
                'value' => $status->value,
                'label' => $status->getLabel()
            ]),
            'document_types' => DocumentType::all(['id', 'title']),
            'users' => User::all(['id', 'name', 'email'])
        ]);
    }

    /**
     * Обновить документ (все поля кроме content)
     */
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'status' => ['required', Rule::in(DocumentStatus::values())],
            'user_id' => 'required|exists:users,id',
            'document_type_id' => 'required|exists:document_types,id',
            'pages_num' => 'nullable|integer|min:1',
            'structure' => 'nullable|array',
            'gpt_settings' => 'nullable|array',
            'thread_id' => 'nullable|string',
        ]);

        $document->update($validated);

        return redirect()->route('admin.documents.show', $document)
            ->with('success', 'Документ успешно обновлен');
    }

    /**
     * Изменить статус документа
     */
    public function updateStatus(Request $request, Document $document)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(DocumentStatus::values())],
        ]);

        $document->update(['status' => $validated['status']]);

        return back()->with('success', 'Статус документа изменен');
    }

    /**
     * Перенести документ к другому пользователю
     */
    public function transferToUser(Request $request, Document $document)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $oldUser = $document->user;
        $newUser = User::find($validated['user_id']);

        $document->update(['user_id' => $validated['user_id']]);

        return back()->with('success', "Документ перенесен от пользователя {$oldUser->name} к {$newUser->name}");
    }

    /**
     * Удалить документ
     */
    public function destroy(Document $document)
    {
        $document->delete();

        return redirect()->route('admin.documents.index')
            ->with('success', 'Документ успешно удален');
    }

    /**
     * Восстановить удаленный документ
     */
    public function restore($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->restore();

        return back()->with('success', 'Документ восстановлен');
    }

    /**
     * Окончательно удалить документ
     */
    public function forceDelete($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->forceDelete();

        return redirect()->route('admin.documents.index')
            ->with('success', 'Документ окончательно удален');
    }
} 