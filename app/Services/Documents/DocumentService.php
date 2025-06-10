<?php

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
        return Document::create([
            'user_id' => $data['user_id'],
            'document_type_id' => $data['document_type_id'],
            'title' => $data['title'],
            'structure' => $data['structure'],
            'status' => $data['status'] ?? 'draft'
        ]);
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
        $document->update([
            'title' => $data['title'] ?? $document->title,
            'structure' => $data['structure'] ?? $document->structure,
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
} 