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

            // Если заказ связан с документом, перенаправляем на страницу документа
            if ($order->document_id) {
                return redirect()->route('documents.show', $order->document_id)
                    ->with('success', 'Оплата успешно завершена');
            }

            // Если заказ без документа, перенаправляем на страницу заказа или дашборд
            return redirect()->route('dashboard')
                ->with('success', 'Баланс успешно пополнен');

        } catch (Exception $e) {
            // Определяем куда перенаправить в случае ошибки
            $redirectRoute = $order && $order->document_id 
                ? route('documents.show', $order->document_id)
                : route('dashboard');

            return redirect($redirectRoute)
                ->with('error', 'Ошибка при обработке платежа: ' . $e->getMessage());
        }
    }

    /**
     * Обработать завершение оплаты для заказа без документа
     * @deprecated Используйте handlePaymentComplete
     */
    public function handlePaymentCompleteWithoutDocument(Request $request, int $orderId)
    {
        return $this->handlePaymentComplete($request, $orderId);
    }
} 