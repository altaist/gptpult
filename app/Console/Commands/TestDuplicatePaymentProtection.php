<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\YookassaPaymentService;
use App\Services\Orders\TransitionService;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Log;

class TestDuplicatePaymentProtection extends Command
{
    protected $signature = 'test:duplicate-payment-protection {orderId?}';
    protected $description = 'Тестирует защиту от дублирования платежей';

    protected YookassaPaymentService $yookassaService;
    protected TransitionService $transitionService;

    public function __construct(YookassaPaymentService $yookassaService, TransitionService $transitionService)
    {
        parent::__construct();
        $this->yookassaService = $yookassaService;
        $this->transitionService = $transitionService;
    }

    public function handle()
    {
        $orderId = $this->argument('orderId');
        
        if (!$orderId) {
            $this->error('Требуется ID заказа');
            return 1;
        }

        $order = Order::with('user')->find($orderId);
        if (!$order) {
            $this->error('Заказ не найден');
            return 1;
        }

        $this->info("Тестирование защиты от дублирования для заказа #{$orderId}");
        
        // Получаем текущий баланс пользователя
        $initialBalance = $order->user->balance_rub ?? 0;
        $this->info("Начальный баланс пользователя: {$initialBalance} руб.");
        
        // Создаем или обновляем платеж в базе данных
        $testPaymentId = 'test-payment-' . time();
        $payment = $order->payments()->first();
        
        if (!$payment) {
            $this->error("У заказа нет платежей. Создаем новый платеж.");
            $payment = \App\Models\Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->amount,
                'status' => 'pending',
                'payment_data' => [
                    'payment_method' => 'yookassa',
                    'yookassa_payment_id' => $testPaymentId
                ]
            ]);
        } else {
            // Обновляем существующий платеж
            $paymentData = $payment->payment_data ?? [];
            $paymentData['yookassa_payment_id'] = $testPaymentId;
            $payment->update([
                'status' => 'pending',
                'payment_data' => $paymentData
            ]);
        }
        
        // Создаем тестовые данные платежа
        $paymentData = [
            'id' => $testPaymentId,
            'status' => 'succeeded',
            'amount' => [
                'value' => $order->amount,
                'currency' => 'RUB'
            ],
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id
            ]
        ];

        $this->info("Первая обработка платежа...");
        $result1 = $this->yookassaService->forceHandleSuccessfulPayment($order, $paymentData);
        
        // Обновляем данные пользователя
        $order->user->refresh();
        $balanceAfterFirst = $order->user->balance_rub ?? 0;
        $this->info("Баланс после первой обработки: {$balanceAfterFirst} руб.");
        
        $this->info("Вторая обработка того же платежа (должна быть заблокирована)...");
        $result2 = $this->yookassaService->forceHandleSuccessfulPayment($order, $paymentData);
        
        // Обновляем данные пользователя
        $order->user->refresh();
        $balanceAfterSecond = $order->user->balance_rub ?? 0;
        $this->info("Баланс после второй обработки: {$balanceAfterSecond} руб.");
        
        // Проверяем результаты
        $expectedBalance = $initialBalance + $order->amount;
        
        if ($balanceAfterFirst == $expectedBalance && $balanceAfterSecond == $expectedBalance) {
            $this->info("✅ ТЕСТ ПРОЙДЕН: Защита от дублирования работает корректно");
            $this->info("- Первая обработка: успешно начислила средства");
            $this->info("- Вторая обработка: корректно заблокирована");
            return 0;
        } else {
            $this->error("❌ ТЕСТ НЕ ПРОЙДЕН: Обнаружено дублирование");
            $this->error("Ожидаемый баланс: {$expectedBalance}");
            $this->error("Фактический баланс: {$balanceAfterSecond}");
            return 1;
        }
    }
} 