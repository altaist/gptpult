<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentTransferService
{
    /**
     * Перенести документы от временного пользователя к авторизованному
     *
     * @param User $fromUser Временный пользователь (с @auto.user email)
     * @param User $toUser Авторизованный пользователь
     * @return array Результат переноса
     */
    public function transferDocuments(User $fromUser, User $toUser): array
    {
        // Проверяем, что это действительно временный пользователь
        if (!$this->isTempUser($fromUser)) {
            return [
                'success' => false,
                'message' => 'Пользователь не является временным',
                'transferred_count' => 0
            ];
        }

        // Проверяем, что пользователи разные
        if ($fromUser->id === $toUser->id) {
            return [
                'success' => false,
                'message' => 'Нельзя перенести документы на того же пользователя',
                'transferred_count' => 0
            ];
        }

        try {
            return DB::transaction(function () use ($fromUser, $toUser) {
                // Получаем все документы временного пользователя
                $documents = Document::where('user_id', $fromUser->id)->get();
                
                if ($documents->isEmpty()) {
                    Log::info('Нет документов для переноса', [
                        'from_user_id' => $fromUser->id,
                        'to_user_id' => $toUser->id
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Нет документов для переноса',
                        'transferred_count' => 0
                    ];
                }

                // Переносим документы
                $transferredCount = 0;
                foreach ($documents as $document) {
                    $document->update(['user_id' => $toUser->id]);
                    $transferredCount++;
                    
                    Log::info('Документ перенесен', [
                        'document_id' => $document->id,
                        'document_title' => $document->title,
                        'from_user_id' => $fromUser->id,
                        'to_user_id' => $toUser->id
                    ]);
                }

                // Переносим заказы (если есть)
                $orders = $fromUser->orders()->get();
                foreach ($orders as $order) {
                    $order->update(['user_id' => $toUser->id]);
                }

                Log::info('Документы успешно перенесены', [
                    'from_user_id' => $fromUser->id,
                    'from_user_email' => $fromUser->email,
                    'to_user_id' => $toUser->id,
                    'to_user_email' => $toUser->email,
                    'transferred_documents' => $transferredCount,
                    'transferred_orders' => $orders->count()
                ]);

                return [
                    'success' => true,
                    'message' => "Успешно перенесено документов: {$transferredCount}",
                    'transferred_count' => $transferredCount,
                    'transferred_orders' => $orders->count()
                ];
            });
        } catch (\Exception $e) {
            Log::error('Ошибка при переносе документов', [
                'from_user_id' => $fromUser->id,
                'to_user_id' => $toUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка при переносе документов: ' . $e->getMessage(),
                'transferred_count' => 0
            ];
        }
    }

    /**
     * Найти временного пользователя по токену авторизации
     *
     * @param string $authToken
     * @return User|null
     */
    public function findTempUserByAuthToken(string $authToken): ?User
    {
        return User::where('auth_token', $authToken)
            ->where('email', 'like', '%@auto.user')
            ->first();
    }

    /**
     * Проверить, является ли пользователь временным
     *
     * @param User $user
     * @return bool
     */
    public function isTempUser(User $user): bool
    {
        return $user->email && (str_ends_with($user->email, '@auto.user') || str_ends_with($user->email, '@linked.user'));
    }

    /**
     * Удалить временного пользователя после переноса документов
     *
     * @param User $tempUser
     * @return bool
     */
    public function deleteTempUser(User $tempUser): bool
    {
        if (!$this->isTempUser($tempUser)) {
            Log::warning('Попытка удалить не временного пользователя', [
                'user_id' => $tempUser->id,
                'email' => $tempUser->email
            ]);
            return false;
        }

        // Проверяем, что у пользователя нет документов
        $documentsCount = Document::where('user_id', $tempUser->id)->count();
        if ($documentsCount > 0) {
            Log::warning('Нельзя удалить временного пользователя с документами', [
                'user_id' => $tempUser->id,
                'documents_count' => $documentsCount
            ]);
            return false;
        }

        try {
            $tempUser->delete();
            
            Log::info('Временный пользователь удален', [
                'user_id' => $tempUser->id,
                'email' => $tempUser->email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Ошибка при удалении временного пользователя', [
                'user_id' => $tempUser->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
} 