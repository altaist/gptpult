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
    const FULL_GENERATION_PRICE = 100.00;

    /**
     * Создать заказ (с документом или без него)
     *
     * @param User $user
     * @param Document|null $document
     * @param float|null $amount
     * @param array $orderData
     * @return Order
     * @throws Exception
     */
    public function createOrder(
        User $user, 
        ?Document $document = null, 
        ?float $amount = null, 
        array $orderData = []
    ): Order {
        // Если передан документ, проверяем права доступа
        if ($document && $document->user_id !== $user->id) {
            throw new Exception('Документ не принадлежит данному пользователю');
        }

        return DB::transaction(function () use ($user, $document, $amount, $orderData) {
            // Если есть документ, загружаем documentType
            if ($document) {
                $document->load('documentType');
            }
            
            $baseOrderData = ['created_at' => now()->toISOString()];
            
            // Добавляем данные документа, если он есть
            if ($document) {
                $baseOrderData = array_merge($baseOrderData, [
                    'document_title' => $document->title,
                    'document_type' => $document->documentType?->name ?? 'Неизвестно',
                ]);
            }
            
            $order = Order::create([
                'user_id' => $user->id,
                'document_id' => $document?->id,
                'amount' => $amount ?? self::DEFAULT_PRICE,
                'order_data' => array_merge($baseOrderData, $orderData)
            ]);

            return $order;
        });
    }

    /**
     * Создать заказ для документа
     * @deprecated Используйте createOrder($user, $document, $amount, $orderData)
     */
    public function createOrderForDocument(
        User $user, 
        Document $document, 
        ?float $amount = null, 
        array $orderData = []
    ): Order {
        return $this->createOrder($user, $document, $amount, $orderData);
    }

    /**
     * Создать заказ без документа
     * @deprecated Используйте createOrder($user, null, $amount, $orderData)
     */
    public function createOrderWithoutDocument(
        User $user,
        ?float $amount = null,
        array $orderData = []
    ): Order {
        return $this->createOrder($user, null, $amount, $orderData);
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