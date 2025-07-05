<?php

namespace App\Services\Documents;

use App\Models\User;
use App\Models\Document;
use App\Models\Payment;
use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\DB;

class DocumentLimitService
{
    /**
     * Базовый лимит для пользователей без платежей
     */
    const BASE_LIMIT = 3;

    /**
     * Количество слотов за каждый полностью сгенерированный документ
     */
    const SLOTS_PER_FULL_DOCUMENT = 4;

    /**
     * Проверить, может ли пользователь создать новый документ
     *
     * @param User $user
     * @return array
     */
    public function canCreateDocument(User $user): array
    {
        $hasPayments = $this->userHasPayments($user);
        $currentDocumentsCount = $this->getUserDocumentsCount($user);
        $availableSlots = $this->calculateAvailableSlots($user, $hasPayments);

        $canCreate = $currentDocumentsCount < $availableSlots;

        return [
            'can_create' => $canCreate,
            'has_payments' => $hasPayments,
            'current_documents' => $currentDocumentsCount,
            'available_slots' => $availableSlots,
            'used_slots' => $currentDocumentsCount,
            'remaining_slots' => max(0, $availableSlots - $currentDocumentsCount)
        ];
    }

    /**
     * Проверить, есть ли у пользователя платежи
     *
     * @param User $user
     * @return bool
     */
    private function userHasPayments(User $user): bool
    {
        return Payment::where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Получить количество документов пользователя
     *
     * @param User $user
     * @return int
     */
    private function getUserDocumentsCount(User $user): int
    {
        return Document::where('user_id', $user->id)->count();
    }

    /**
     * Получить количество полностью сгенерированных документов
     *
     * @param User $user
     * @return int
     */
    private function getFullyGeneratedDocumentsCount(User $user): int
    {
        return Document::where('user_id', $user->id)
            ->where('status', DocumentStatus::FULL_GENERATED)
            ->count();
    }

    /**
     * Вычислить доступные слоты для создания документов
     *
     * @param User $user
     * @param bool $hasPayments
     * @return int
     */
    private function calculateAvailableSlots(User $user, bool $hasPayments): int
    {
        if (!$hasPayments) {
            return self::BASE_LIMIT;
        }

        $fullyGeneratedCount = $this->getFullyGeneratedDocumentsCount($user);
        $additionalSlots = $fullyGeneratedCount * self::SLOTS_PER_FULL_DOCUMENT;

        return self::BASE_LIMIT + $additionalSlots;
    }

    /**
     * Получить сообщение об ограничении для пользователя
     *
     * @param User $user
     * @return string
     */
    public function getLimitMessage(User $user): string
    {
        $limitInfo = $this->canCreateDocument($user);

        if ($limitInfo['can_create']) {
            return '';
        }

        if (!$limitInfo['has_payments']) {
            return 'Вы достигли лимита создания документов. Для продолжения работы необходимо пополнить баланс.';
        }

        return 'Вы достигли лимита создания документов. Завершите генерацию существующих документов или пополните баланс.';
    }
} 