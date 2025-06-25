<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Enums\DocumentStatus;

class DocumentService
{
    /**
     * Создает новый документ
     *
     * @param array $data
     * @return Document
     */
    public function create(array $data): Document
    {
        // Создаем документ
        $document = Document::create([
            'user_id' => $data['user_id'],
            'document_type_id' => $data['document_type_id'],
            'title' => $data['title'],
            'structure' => $data['structure'],
            'content' => $data['content'] ?? null,
            'pages_num' => $data['pages_num'] ?? null,
            'gpt_settings' => $data['gpt_settings'] ?? null,
            'status' => $data['status'] ?? 'draft'
        ]);

        return $document;
    }

    /**
     * Обновляет существующий документ
     *
     * @param Document $document
     * @param array $data
     * @return Document
     */
    public function update(Document $document, array $data): Document
    {
        // Если меняется статус на генерацию, проверяем наличие активных задач
        if (isset($data['status']) && 
            in_array($data['status'], [DocumentStatus::PRE_GENERATING, DocumentStatus::FULL_GENERATING]) &&
            $document->status !== $data['status']
        ) {
            if ($this->hasActiveGenerationJob($document)) {
                throw new \Exception('Для этого документа уже запущена задача генерации');
            }
        }

        $document->update([
            'title' => $data['title'] ?? $document->title,
            'structure' => $data['structure'] ?? $document->structure,
            'content' => $data['content'] ?? $document->content,
            'pages_num' => $data['pages_num'] ?? $document->pages_num,
            'gpt_settings' => $data['gpt_settings'] ?? $document->gpt_settings,
            'status' => $data['status'] ?? $document->status,
            'document_type_id' => $data['document_type_id'] ?? $document->document_type_id,
        ]);

        return $document->fresh();
    }

    /**
     * Мягкое удаление документа
     *
     * @param Document $document
     * @return bool
     */
    public function delete(Document $document): bool
    {
        return $document->delete();
    }

    /**
     * Получает документ по ID
     *
     * @param int $id
     * @return Document|null
     */
    public function find(int $id): ?Document
    {
        return Document::find($id);
    }

    /**
     * Получает все документы пользователя
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserDocuments(int $userId): Collection
    {
        return Document::where('user_id', $userId)->get();
    }

    /**
     * Получает документы с пагинацией
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Document::with(['user', 'documentType'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Изменяет статус документа
     *
     * @param Document $document
     * @param string $status
     * @return Document
     */
    public function changeStatus(Document $document, string $status): Document
    {
        $document->update(['status' => $status]);
        return $document->fresh();
    }

    /**
     * Обновляет тему документа
     *
     * @param Document $document
     * @param string $topic
     * @return Document
     */
    public function updateTopic(Document $document, string $topic): Document
    {
        $structure = $document->structure;
        $structure['topic'] = $topic;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновляет цели документа
     *
     * @param Document $document
     * @param array $objectives
     * @return Document
     */
    public function updateObjectives(Document $document, array $objectives): Document
    {
        $structure = $document->structure;
        $structure['objectives'] = $objectives;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Добавляет новую цель в документ
     *
     * @param Document $document
     * @param string $objective
     * @return Document
     */
    public function addObjective(Document $document, string $objective): Document
    {
        $structure = $document->structure;
        $structure['objectives'][] = $objective;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновляет содержимое документа
     *
     * @param Document $document
     * @param array $contents
     * @return Document
     */
    public function updateContents(Document $document, array $contents): Document
    {
        $structure = $document->structure;
        $structure['contents'] = $contents;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Добавляет новую тему в содержимое
     *
     * @param Document $document
     * @param string $title
     * @param array $subtopics
     * @return Document
     */
    public function addContentTopic(Document $document, string $title, array $subtopics): Document
    {
        $structure = $document->structure;
        $structure['contents'][] = [
            'title' => $title,
            'subtopics' => $subtopics
        ];
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновляет подтему в существующей теме
     *
     * @param Document $document
     * @param int $topicIndex
     * @param int $subtopicIndex
     * @param array $subtopicData
     * @return Document
     */
    public function updateSubtopic(Document $document, int $topicIndex, int $subtopicIndex, array $subtopicData): Document
    {
        $structure = $document->structure;
        $structure['contents'][$topicIndex]['subtopics'][$subtopicIndex] = array_merge(
            $structure['contents'][$topicIndex]['subtopics'][$subtopicIndex],
            $subtopicData
        );
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновляет список источников
     *
     * @param Document $document
     * @param array $references
     * @return Document
     */
    public function updateReferences(Document $document, array $references): Document
    {
        $structure = $document->structure;
        $structure['references'] = $references;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Добавляет новый источник
     *
     * @param Document $document
     * @param array $reference
     * @return Document
     */
    public function addReference(Document $document, array $reference): Document
    {
        $structure = $document->structure;
        $structure['references'][] = $reference;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Удаляет источник по индексу
     *
     * @param Document $document
     * @param int $index
     * @return Document
     */
    public function removeReference(Document $document, int $index): Document
    {
        $structure = $document->structure;
        unset($structure['references'][$index]);
        $structure['references'] = array_values($structure['references']); // Переиндексация массива
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновляет тезисы документа
     *
     * @param Document $document
     * @param string $theses
     * @return Document
     */
    public function updateTheses(Document $document, string $theses): Document
    {
        $structure = $document->structure;
        $structure['theses'] = $theses;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Добавляет текст к существующим тезисам
     *
     * @param Document $document
     * @param string $additionalTheses
     * @return Document
     */
    public function appendTheses(Document $document, string $additionalTheses): Document
    {
        $structure = $document->structure;
        $currentTheses = $structure['theses'] ?? '';
        $structure['theses'] = $currentTheses . "\n\n" . $additionalTheses;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Очищает тезисы документа
     *
     * @param Document $document
     * @return Document
     */
    public function clearTheses(Document $document): Document
    {
        $structure = $document->structure;
        $structure['theses'] = '';
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Получить неструктурированное текстовое содержание из структуры
     *
     * @param Document $document
     * @return string|null
     */
    public function getContentsText(Document $document): ?string
    {
        return $document->structure['contents-text'] ?? null;
    }

    /**
     * Установить неструктурированное текстовое содержание в структуру
     *
     * @param Document $document
     * @param string $text
     * @return Document
     */
    public function setContentsText(Document $document, string $text): Document
    {
        $structure = $document->structure ?? [];
        $structure['contents-text'] = $text;
        
        $document->update(['structure' => $structure]);
        return $document->fresh();
    }

    /**
     * Обновить содержание документа (поле content)
     *
     * @param Document $document
     * @param string $content
     * @return Document
     */
    public function updateContent(Document $document, string $content): Document
    {
        $document->update(['content' => $content]);
        return $document->fresh();
    }

    /**
     * Обновить количество страниц документа
     *
     * @param Document $document
     * @param int $pagesNum
     * @return Document
     */
    public function updatePagesNum(Document $document, int $pagesNum): Document
    {
        $document->update(['pages_num' => $pagesNum]);
        return $document->fresh();
    }

    /**
     * Обновить настройки GPT для документа
     *
     * @param Document $document
     * @param array $gptSettings
     * @return Document
     */
    public function updateGptSettings(Document $document, array $gptSettings): Document
    {
        $document->update(['gpt_settings' => $gptSettings]);
        return $document->fresh();
    }

    /**
     * Проверяет наличие активной задачи генерации для документа
     *
     * @param Document $document
     * @return bool
     */
    public function hasActiveGenerationJob(Document $document): bool
    {
        $jobTypes = ['StartGenerateDocument', 'StartFullGenerateDocument'];
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Проверяем наличие активной задачи в очереди
        $hasActiveJob = cache()->remember(
            'document_has_active_job_' . $document->id,
            now()->addSeconds(5),
            function () use ($documentIdPattern, $jobTypes) {
                return DB::table('jobs')
                    ->where('payload', 'like', $documentIdPattern)
                    ->where(function ($q) use ($jobTypes) {
                        foreach ($jobTypes as $type) {
                            $q->orWhere('payload', 'like', '%' . $type . '%');
                        }
                    })
                    ->exists();
            }
        );

        if ($hasActiveJob) {
            return true;
        }

        // Проверяем failed jobs
        return DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) use ($jobTypes) {
                foreach ($jobTypes as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->exists();
    }

    /**
     * Удаляет все задания генерации для документа
     *
     * @param Document $document
     * @return array Количество удаленных заданий ['active' => int, 'failed' => int]
     */
    public function deleteGenerationJobs(Document $document): array
    {
        $jobTypes = ['StartGenerateDocument', 'StartFullGenerateDocument'];
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Удаляем активные задания
        $activeJobsDeleted = DB::table('jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) use ($jobTypes) {
                foreach ($jobTypes as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->delete();

        // Удаляем неудачные задания
        $failedJobsDeleted = DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) use ($jobTypes) {
                foreach ($jobTypes as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->delete();

        // Очищаем кэш проверки активных заданий
        cache()->forget('document_has_active_job_' . $document->id);

        return [
            'active' => $activeJobsDeleted,
            'failed' => $failedJobsDeleted
        ];
    }

    /**
     * Получить статус job документа
     *
     * @param Document $document
     * @return array
     */
    public function getJobStatus(Document $document): array
    {
        $jobTypes = ['StartGenerateDocument', 'StartFullGenerateDocument'];
        $documentIdPattern = '%"document_id":' . $document->id . '%';

        // Получаем информацию о job из базы данных
        $job = DB::table('jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) use ($jobTypes) {
                foreach ($jobTypes as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->first();

        // Проверяем failed jobs
        $failedJob = DB::table('failed_jobs')
            ->where('payload', 'like', $documentIdPattern)
            ->where(function ($q) use ($jobTypes) {
                foreach ($jobTypes as $type) {
                    $q->orWhere('payload', 'like', '%' . $type . '%');
                }
            })
            ->first();

        if ($failedJob) {
            return [
                'status' => 'failed',
                'message' => 'Задача завершилась с ошибкой',
                'error' => json_decode($failedJob->exception, true),
                'failed_at' => $failedJob->failed_at
            ];
        }

        if ($job) {
            return [
                'status' => 'processing',
                'message' => 'Задача выполняется',
                'attempts' => $job->attempts,
                'created_at' => $job->created_at,
                'available_at' => $job->available_at
            ];
        }

        return [
            'status' => 'not_found',
            'message' => 'Задача не найдена в очереди'
        ];
    }
} 