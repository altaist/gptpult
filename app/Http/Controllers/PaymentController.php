<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\PaymentService;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected DocumentJobService $documentJobService;
    protected TransitionService $transitionService;

    public function __construct(
        PaymentService $paymentService,
        DocumentJobService $documentJobService,
        TransitionService $transitionService
    ) {
        $this->paymentService = $paymentService;
        $this->documentJobService = $documentJobService;
        $this->transitionService = $transitionService;
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
            $order = Order::with('document')->find($orderId);

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

            // Если заказ связан с документом
            if ($order->document_id && $order->document) {
                $document = $order->document;
                
                // Проверяем, можно ли запустить полную генерацию
                if ($document->status->canStartFullGenerationWithReferences($document)) {
                    try {
                        // Запускаем полную генерацию автоматически
                        $this->documentJobService->startFullGeneration($document, $this->transitionService);
                        
                        Log::info('Автоматически запущена полная генерация после оплаты', [
                            'document_id' => $document->id,
                            'order_id' => $order->id,
                            'payment_id' => $payment->id
                        ]);

                        return redirect()->route('documents.show', $order->document_id)
                            ->with('success', 'Оплата успешно завершена. Генерация документа запущена автоматически.');
                    } catch (Exception $e) {
                        Log::error('Ошибка при автоматическом запуске генерации после оплаты', [
                            'document_id' => $document->id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);

                        return redirect()->route('documents.show', $order->document_id)
                            ->with('success', 'Оплата успешно завершена.')
                            ->with('warning', 'Генерация документа не была запущена автоматически. Вы можете запустить её вручную.');
                    }
                } else {
                    Log::info('Автоматический запуск генерации невозможен - документ не готов', [
                        'document_id' => $document->id,
                        'order_id' => $order->id,
                        'document_status' => $document->status->value
                    ]);

                    return redirect()->route('documents.show', $order->document_id)
                        ->with('success', 'Оплата успешно завершена');
                }
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