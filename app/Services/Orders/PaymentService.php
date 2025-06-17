<?php

namespace App\Services\Orders;

use App\Models\User;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Enums\OrderStatus;
use App\Enums\DocumentStatus;

class PaymentService
{
    protected TransitionService $transitionService;

    public function __construct(TransitionService $transitionService)
    {
        $this->transitionService = $transitionService;
    }

    /**
     * Создать платеж для заказа
     *
     * @param int $orderId
     * @param float $amount
     * @param array $paymentData
     * @param bool $autoComplete
     * @return Payment
     * @throws Exception
     */
    public function createPaymentForOrder(
        int $orderId, 
        float $amount, 
        array $paymentData = [],
        bool $autoComplete = true
    ): Payment {
        $order = Order::with(['user', 'document'])->find($orderId);

        if (!$order) {
            throw new Exception('Заказ не найден');
        }

        if ($amount <= 0) {
            throw new Exception('Сумма платежа должна быть положительной');
        }

        return DB::transaction(function () use ($order, $amount, $paymentData, $autoComplete) {
            // Создаем платеж
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $amount,
                'status' => $autoComplete ? 'completed' : 'pending',
                'payment_data' => array_merge([
                    'order_amount' => $order->amount,
                    'document_title' => $order->document->title ?? 'Неизвестно',
                    'payment_method' => 'balance',
                    'created_at' => now()->toISOString()
                ], $paymentData)
            ]);

            // Если платеж автоматически завершается, пополняем баланс и меняем статус заказа
            if ($autoComplete) {
                $this->transitionService->creditUser(
                    $order->user,
                    $amount,
                    "Платеж #{$payment->id} за документ \"{$order->document->title}\""
                );
                $order->update(['status' => OrderStatus::PAID]);
            }

            return $payment;
        });
    }

    /**
     * Подтвердить платеж и пополнить баланс
     *
     * @param Payment $payment
     * @return Payment
     * @throws Exception
     */
    public function confirmPayment(Payment $payment): Payment
    {
        if ($payment->status !== 'pending') {
            throw new Exception('Платеж уже обработан или отменен');
        }

        return DB::transaction(function () use ($payment) {
            // Обновляем статус платежа
            $payment->update(['status' => 'completed']);

            // Пополняем баланс пользователя
            $this->transitionService->creditUser(
                $payment->user,
                $payment->amount,
                "Платеж #{$payment->id} за заказ #{$payment->order_id}"
            );

            return $payment->fresh();
        });
    }

    /**
     * Отменить платеж
     *
     * @param Payment $payment
     * @param string $reason
     * @return Payment
     * @throws Exception
     */
    public function cancelPayment(Payment $payment, string $reason = ''): Payment
    {
        if ($payment->status === 'completed') {
            throw new Exception('Нельзя отменить завершенный платеж');
        }

        return DB::transaction(function () use ($payment, $reason) {
            $paymentData = $payment->payment_data ?? [];
            $paymentData['cancellation_reason'] = $reason;
            $paymentData['cancelled_at'] = now()->toISOString();

            $payment->update([
                'status' => 'cancelled',
                'payment_data' => $paymentData
            ]);

            return $payment;
        });
    }

    /**
     * Получить платежи пользователя
     *
     * @param User $user
     * @return Collection
     */
    public function getUserPayments(User $user): Collection
    {
        return $user->payments()
            ->with(['order.document'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить платежи по заказу
     *
     * @param Order $order
     * @return Collection
     */
    public function getOrderPayments(Order $order): Collection
    {
        return $order->payments()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получить все платежи (для админов)
     *
     * @param int $limit
     * @return Collection
     */
    public function getAllPayments(int $limit = 50): Collection
    {
        return Payment::with(['user', 'order.document'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить платежи по статусу
     *
     * @param string $status
     * @param int $limit
     * @return Collection
     */
    public function getPaymentsByStatus(string $status, int $limit = 50): Collection
    {
        return Payment::with(['user', 'order.document'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
} 