<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Orders\OrderService;
use App\Services\Orders\PaymentProcessHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected PaymentProcessHelper $paymentHelper;

    public function __construct(OrderService $orderService, PaymentProcessHelper $paymentHelper)
    {
        $this->orderService = $orderService;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Обработать заказ (с документом или без документа)
     *
     * @param Request $request
     * @param Document|null $document
     * @return \Illuminate\Http\JsonResponse
     */
    public function processOrder(Request $request, ?Document $document = null)
    {
        try {
            $user = Auth::user();
            $amount = $request->input('amount');
            $orderData = $request->input('order_data', []);

            // Если передан документ, проверяем права доступа
            if ($document && $document->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'У вас нет доступа к этому документу'
                ], 403);
            }

            // Создаем заказ (с документом или без него)
            $order = $this->orderService->createOrder($user, $document, $amount, $orderData);

            Log::info('Заказ успешно создан', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => $order->amount,
                'document_id' => $document?->id,
                'order_data' => $orderData
            ]);

            // Проверяем, это запрос на пополнение баланса или для документа
            $isPurposeBalanceTopUp = isset($orderData['purpose']) && $orderData['purpose'] === 'balance_top_up';

            if ($isPurposeBalanceTopUp || !$document) {
                // Для пополнения баланса возвращаем данные заказа
                return response()->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'amount' => $order->amount
                ]);
            } else {
                // Для документов - старая логика с PaymentProcessHelper
                // Загружаем отношение document для использования в PaymentProcessHelper
                $order->load('document');

                // Создаем ссылку для оплаты
                $paymentUrl = $this->paymentHelper->createPaymentLink($order);

                return response()->json([
                    'redirect' => $paymentUrl
                ]);
            }

        } catch (Exception $e) {
            // Логируем детальную информацию об ошибке
            Log::error('Ошибка при создании заказа', [
                'document_id' => $document?->id,
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Ошибка при создании заказа: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Создать заказ без документа
     * @deprecated Используйте processOrder без параметра document
     */
    public function processOrderWithoutDocument(Request $request)
    {
        return $this->processOrder($request, null);
    }
} 