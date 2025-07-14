<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentType;
use App\Services\Documents\DocumentService;
use App\Services\Documents\DocumentJobService;
use App\Services\Documents\Files\WordDocumentService;
use App\Services\Telegram\TelegramNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    protected DocumentService $documentService;
    protected DocumentJobService $documentJobService;
    protected WordDocumentService $wordDocumentService;
    protected TelegramNotificationService $telegramNotificationService;
    protected \App\Services\RecaptchaService $recaptchaService;

    public function __construct(
        DocumentService $documentService,
        DocumentJobService $documentJobService,
        WordDocumentService $wordDocumentService,
        TelegramNotificationService $telegramNotificationService,
        \App\Services\RecaptchaService $recaptchaService
    ) {
        $this->documentService = $documentService;
        $this->documentJobService = $documentJobService;
        $this->wordDocumentService = $wordDocumentService;
        $this->telegramNotificationService = $telegramNotificationService;
        $this->recaptchaService = $recaptchaService;
    }

    /**
     * Показать список документов (доступ запрещен)
     */
    public function index()
    {
        abort(403, 'У вас нет прав для просмотра списка документов');
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

        return Inertia::render('documents/ShowDocumentModern', [
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
            'orderPrice' => $orderPrice,
            'user' => $document->user->only(['id', 'name', 'email', 'telegram_id', 'telegram_username', 'privacy_consent'])
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
        try {
            $user = Auth::user();
            
            // Проверяем лимиты перед созданием документа
            $documentLimitService = app(\App\Services\Documents\DocumentLimitService::class);
            $limitCheck = $documentLimitService->canCreateDocument($user);
            
            if (!$limitCheck['can_create']) {
                return response()->json([
                    'message' => $documentLimitService->getLimitMessage($user),
                    'limit_reached' => true,
                    'limit_info' => $limitCheck
                ], 422);
            }
            
            $validated = $request->validate([
                'document_type_id' => ['required', 'exists:document_types,id'],
                'topic' => ['required', 'string', 'min:10', 'max:255'],
                'pages_num' => ['nullable', 'integer', 'min:3', 'max:25'],
                'test' => ['nullable', 'boolean'], // Параметр для тестирования с фейковыми данными
                'recaptcha_token' => ['nullable', 'string'], // Токен reCAPTCHA
            ]);

            // Проверяем reCAPTCHA, если включена
            if ($this->recaptchaService->isEnabled() && isset($validated['recaptcha_token'])) {
                $recaptchaResult = $this->recaptchaService->verifyV3(
                    $validated['recaptcha_token'],
                    'document_create',
                    0.5, // Минимальный скор
                    $request->ip()
                );

                if (!$recaptchaResult['success']) {
                    return response()->json([
                        'message' => 'Проверка безопасности не пройдена. Попробуйте ещё раз.',
                        'recaptcha_error' => $recaptchaResult['message'] ?? 'reCAPTCHA verification failed'
                    ], 422);
                }
            } elseif ($this->recaptchaService->isEnabled()) {
                return response()->json([
                    'message' => 'Требуется подтверждение безопасности',
                    'recaptcha_required' => true
                ], 422);
            }

            // Если передан параметр test, создаем с фейковыми данными из фабрики
            if ($request->boolean('test')) {
                $document = Document::factory()->create([
                    'user_id' => Auth::id(),
                    'document_type_id' => $validated['document_type_id'],
                    'title' => $validated['topic'],
                    'pages_num' => $validated['pages_num'] ?? 10,
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
                    'pages_num' => $validated['pages_num'] ?? 10,
                    'status' => 'pre_generating', // Сразу устанавливаем статус генерации
                ]);

                // Обновляем topic в структуре документа
                $structure = $document->structure ?? [];
                $structure['topic'] = $validated['topic'];
                $document->structure = $structure;
                $document->save();
            }

            // Запускаем генерацию через сервис
            $result = $this->documentJobService->safeStartBaseGeneration($document);

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'document' => $document
                ], 422);
            }

            return response()->json([
                'message' => 'Документ успешно создан',
                'document' => $document,
                'redirect_url' => route('documents.show', ['document' => $document->id, 'autoload' => 1])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Ошибка валидации данных',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при быстром создании документа', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Ошибка при создании документа: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверить статус документа
     */
    public function checkStatus(Document $document)
    {
        $this->authorize('view', $document);

        $statusEnum = $document->status;
        $jobStatus = $this->documentJobService->getJobStatus($document);

        return response()->json([
            'document_id' => $document->id,
            'status' => $statusEnum->value,
            'status_label' => $statusEnum->getLabel(),
            'status_color' => $statusEnum->getColor(),
            'status_icon' => $statusEnum->getIcon(),
            'is_final' => $statusEnum->isFinal(),
            'is_generating' => $statusEnum->isGenerating(),
            'can_start_full_generation' => $statusEnum->canStartFullGenerationWithReferences($document),
            'is_fully_generated' => $statusEnum->isFullyGenerated(),
            'title' => $document->title,
            'updated_at' => $document->updated_at,
            'has_contents' => !empty($document->structure['contents']),
            'has_objectives' => !empty($document->structure['objectives']),
            'has_detailed_contents' => !empty($document->structure['detailed_contents']),
            'has_introduction' => !empty($document->structure['introduction']),
            'has_conclusion' => !empty($document->structure['conclusion']),
            'has_references' => !empty($document->structure['references']),
            'structure_complete' => !empty($document->structure['contents']) && !empty($document->structure['objectives']),
            'document' => $document->load('documentType'), // Добавляем полные данные документа
            'job_status' => $jobStatus // Добавляем информацию о статусе задания
        ]);
    }

    /**
     * Сгенерировать и скачать Word-документ
     */
    public function downloadWord(Document $document, Request $request)
    {
        $this->authorize('view', $document);

        try {
            // Проверяем, есть ли уже существующий файл документа
            $file = $document->files()
                ->where('mime_type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                ->where('created_at', '>', $document->updated_at) // Файл должен быть новее последнего обновления документа
                ->orderBy('created_at', 'desc')
                ->first();

            // Если файл существует и доступен, используем его
            if ($file && file_exists($file->getFullPath())) {
                \Illuminate\Support\Facades\Log::info('Используется существующий Word файл', [
                    'document_id' => $document->id,
                    'file_id' => $file->id,
                    'file_path' => $file->getFullPath()
                ]);
            } else {
                // Создаем новый файл
                \Illuminate\Support\Facades\Log::info('Создается новый Word файл', [
                    'document_id' => $document->id,
                    'existing_file_found' => $file ? 'yes' : 'no',
                    'file_exists' => $file ? file_exists($file->getFullPath()) : 'no_file'
                ]);
                $file = $this->wordDocumentService->generate($document);
            }

            // Проверяем, работает ли пользователь в Telegram WebApp
            $isTelegramWebApp = $request->header('X-Telegram-User-Id') || 
                               $request->session()->has('telegram_user_id') ||
                               str_contains($request->header('User-Agent', ''), 'Telegram');

            // Если пользователь в Telegram и у него есть связь с ботом, отправляем файл через бот
            if ($isTelegramWebApp && $document->user->telegram_id) {
                try {
                    $this->telegramNotificationService->sendDocumentFile($document->user, $file);
                    
                    return response()->json([
                        'message' => 'Документ отправлен в Telegram',
                        'url' => $file->getPublicUrl(),
                        'filename' => $file->display_name,
                        'telegram_sent' => true
                    ]);
                } catch (\Exception $e) {
                    // Если не удалось отправить через Telegram, возвращаем обычную ссылку
                    \Illuminate\Support\Facades\Log::warning('Failed to send document via Telegram', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Для обычного браузера - возвращаем файл для прямого скачивания
            return response()->download($file->getFullPath(), $file->display_name, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при скачивании документа', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
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
     * Обновить заголовок документа
     */
    public function updateTitle(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'], // Увеличиваем лимит для структуры
        ]);

        $this->documentService->updateStructureTitle($document, $validated['title']);

        return response()->json([
            'message' => 'Заголовок документа успешно обновлен',
            'title' => $validated['title']
        ]);
    }

    /**
     * Обновить внутренний заголовок документа
     */
    public function updateDocumentTitle(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'document_title' => ['required', 'string', 'max:255'],
        ]);

        $this->documentService->updateDocumentTitle($document, $validated['document_title']);

        return response()->json([
            'message' => 'Заголовок документа успешно обновлен',
            'document_title' => $validated['document_title']
        ]);
    }

    /**
     * Обновить описание документа
     */
    public function updateDescription(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'description' => ['required', 'string'],
        ]);

        $this->documentService->updateDescription($document, $validated['description']);

        return response()->json([
            'message' => 'Описание документа успешно обновлено',
            'description' => $validated['description']
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

    /**
     * Удалить все задания генерации документа
     */
    public function deleteGenerationJobs(Document $document)
    {
        $this->authorize('update', $document);

        try {
            $result = $this->documentJobService->deleteJobs($document);

            return response()->json([
                'message' => 'Задания генерации успешно удалены',
                'deleted_jobs' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка при удалении заданий: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Запустить генерацию документа с проверкой существующих заданий
     */
    public function startGeneration(Document $document)
    {
        $this->authorize('update', $document);

        $result = $this->documentJobService->safeStartBaseGeneration($document);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
                'document' => $document
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'document' => $document
        ]);
    }
} 