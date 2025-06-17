<?php

namespace App\Services\Orders;

use App\Models\User;
use App\Models\Document;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    /**
     * Стоимость заказа по умолчанию
     */
    const DEFAULT_PRICE = 290.00;

    /**
     * Создать заказ для документа
     *
     * @param User $user
     * @param Document $document
     * @param float|null $amount
     * @param array $orderData
     * @return Order
     * @throws Exception
     */
    public function createOrderForDocument(
        User $user, 
        Document $document, 
        ?float $amount = null, 
        array $orderData = []
    ): Order {
        // Проверяем, принадлежит ли документ пользователю
        if ($document->user_id !== $user->id) {
            throw new Exception('Документ не принадлежит данному пользователю');
        }
        
        // Удаляем проверку на существование заказа
        // Теперь всегда создается новый заказ

        return DB::transaction(function () use ($user, $document, $amount, $orderData) {
            // Загружаем documentType чтобы избежать N+1 запросов
            $document->load('documentType');
            
            $order = Order::create([
                'user_id' => $user->id,
                'document_id' => $document->id,
                'amount' => $amount ?? self::DEFAULT_PRICE,
                'order_data' => array_merge([
                    'document_title' => $document->title,
                    'document_type' => $document->documentType?->name ?? 'Неизвестно',
                    'created_at' => now()->toISOString()
                ], $orderData)
            ]);

            return $order;
        });
    }

    /**
     * Получить заказы пользователя
     *
     * @param User $user
     * @return Collection
     */
    public function getUserOrders(User $user): Collection
    {
        return $user->orders()
            ->with(['document.documentType'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить заказ по ID
     *
     * @param int $orderId
     * @param User $user
     * @return Order|null
     */
    public function getOrderById(int $orderId, User $user): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->with(['document.documentType', 'payments'])
            ->first();
    }

    /**
     * Получить заказ для документа
     *
     * @param Document $document
     * @param User $user
     * @return Order|null
     */
    public function getOrderForDocument(Document $document, User $user): ?Order
    {
        return Order::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->with(['payments'])
            ->first();
    }

    /**
     * Получить все заказы (для админов)
     *
     * @param int $limit
     * @return Collection
     */
    public function getAllOrders(int $limit = 50): Collection
    {
        return Order::with(['user', 'document.documentType', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Проверить, оплачен ли заказ
     *
     * @param Order $order
     * @return bool
     */
    public function isOrderPaid(Order $order): bool
    {
        $totalPaid = $order->payments()->sum('amount');
        return $totalPaid >= $order->amount;
    }

    /**
     * Получить сумму к доплате
     *
     * @param Order $order
     * @return float
     */
    public function getRemainingAmount(Order $order): float
    {
        $totalPaid = $order->payments()->sum('amount');
        $remaining = $order->amount - $totalPaid;
        return max(0, $remaining);
    }
} 