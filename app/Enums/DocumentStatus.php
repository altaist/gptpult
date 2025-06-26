<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case DRAFT = 'draft';                              // Черновик (создан, но не запущена генерация)
    case PRE_GENERATING = 'pre_generating';            // Генерируется базовая структура
    case PRE_GENERATED = 'pre_generated';              // Базовая структура сгенерирована
    case PRE_GENERATION_FAILED = 'pre_generation_failed'; // Ошибка генерации базовой структуры
    case FULL_GENERATING = 'full_generating';          // Генерируется полное содержимое
    case FULL_GENERATED = 'full_generated';            // Полностью сгенерирован
    case FULL_GENERATION_FAILED = 'full_generation_failed'; // Ошибка полной генерации
    case IN_REVIEW = 'in_review';                     // На проверке
    case APPROVED = 'approved';                       // Утвержден
    case REJECTED = 'rejected';                       // Отклонен

    /**
     * Получить человекочитаемое название статуса
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Черновик',
            self::PRE_GENERATING => 'Генерируется структура...',
            self::PRE_GENERATED => 'Структура готова',
            self::PRE_GENERATION_FAILED => 'Ошибка генерации структуры',
            self::FULL_GENERATING => 'Генерируется содержимое...',
            self::FULL_GENERATED => 'Полностью готов',
            self::FULL_GENERATION_FAILED => 'Ошибка полной генерации',
            self::IN_REVIEW => 'На проверке',
            self::APPROVED => 'Утвержден',
            self::REJECTED => 'Отклонен',
        };
    }

    /**
     * Получить цвет для UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'grey',
            self::PRE_GENERATING => 'primary',
            self::PRE_GENERATED => 'positive',
            self::PRE_GENERATION_FAILED => 'negative',
            self::FULL_GENERATING => 'secondary',
            self::FULL_GENERATED => 'green',
            self::FULL_GENERATION_FAILED => 'red',
            self::IN_REVIEW => 'warning',
            self::APPROVED => 'green-10',
            self::REJECTED => 'red-8',
        };
    }

    /**
     * Получить иконку для UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::DRAFT => 'edit',
            self::PRE_GENERATING => 'sync',
            self::PRE_GENERATED => 'check_circle',
            self::PRE_GENERATION_FAILED => 'error',
            self::FULL_GENERATING => 'autorenew',
            self::FULL_GENERATED => 'task_alt',
            self::FULL_GENERATION_FAILED => 'error_outline',
            self::IN_REVIEW => 'rate_review',
            self::APPROVED => 'verified',
            self::REJECTED => 'cancel',
        };
    }

    /**
     * Проверить, является ли статус финальным (не требует дальнейшего опроса)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::PRE_GENERATED,
            self::FULL_GENERATED,
            self::IN_REVIEW,
            self::APPROVED,
            self::REJECTED,
            self::PRE_GENERATION_FAILED,
            self::FULL_GENERATION_FAILED
        ]);
    }

    /**
     * Проверить, идет ли процесс генерации
     */
    public function isGenerating(): bool
    {
        return in_array($this, [
            self::PRE_GENERATING,
            self::FULL_GENERATING
        ]);
    }

    /**
     * Проверить, можно ли запустить полную генерацию
     */
    public function canStartFullGeneration(): bool
    {
        return in_array($this, [
            self::PRE_GENERATED,
            self::FULL_GENERATION_FAILED
        ]);
    }

    /**
     * Проверить, можно ли запустить полную генерацию для конкретного документа
     * (проверяет и статус, и наличие ссылок)
     */
    public function canStartFullGenerationWithReferences(\App\Models\Document $document): bool
    {
        // Сначала проверяем статус
        if (!$this->canStartFullGeneration()) {
            return false;
        }

        // Затем проверяем наличие ссылок
        $structure = $document->structure ?? [];
        $references = $structure['references'] ?? [];
        
        return !empty($references);
    }

    /**
     * Проверить, завершена ли полная генерация
     */
    public function isFullyGenerated(): bool
    {
        return $this === self::FULL_GENERATED;
    }

    /**
     * Получить все статусы в виде массива для валидации
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
} 