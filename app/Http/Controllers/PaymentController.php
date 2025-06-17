<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Обработать завершение оплаты
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handlePaymentComplete(Request $request, int $orderId)
    {
        try {
            $order = Order::find($orderId);

            if (!$order) {
                throw new Exception('Заказ не найден');
            }

            // Создаем платеж для заказа
            $payment = $this->paymentService->createPaymentForOrder(
                $order->id,
                $order->amount,
                [
                    'payment_method' => 'test',
                    'payment_id' => $request->get('payment_id'),
                    'completed_at' => now()->toISOString()
                ]
            );

            // Перенаправляем на страницу документа
            return redirect()->route('documents.show', $order->document_id)
                ->with('success', 'Оплата успешно завершена');

        } catch (Exception $e) {
            return redirect()->route('documents.show', $order->document_id ?? null)
                ->with('error', 'Ошибка при обработке платежа: ' . $e->getMessage());
        }
    }
} 