<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\URL;

class PaymentProcessHelper
{
    /**
     * Создать ссылку для оплаты заказа
     *
     * @param Order $order
     * @return string
     */
    public function createPaymentLink(Order $order): string
    {
        // По умолчанию ведем на тестовую страницу PaymentTest
        // В будущем здесь будет интеграция с реальными платежными системами
        return route('payment.test', [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'description' => $order->document ? ("Оплата за документ: {$order->document->title}") : ("Оплата заказа #{$order->id}")
        ]);
    }
} 