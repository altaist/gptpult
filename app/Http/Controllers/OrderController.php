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
     * Обработать заказ для документа
     *
     * @param Request $request
     * @param Document $document
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processOrder(Request $request, Document $document)
    {
                try {
            $user = Auth::user();

            // Проверяем права доступа к документу
            if ($document->user_id !== $user->id) {
                return response()->json([
                    'error' => 'У вас нет доступа к этому документу'
                ], 403);
            }

            // Создаем заказ для документа
            $order = $this->orderService->createOrderForDocument($user, $document);

            // Загружаем отношение document для использования в PaymentProcessHelper
            $order->load('document');

            // Создаем ссылку для оплаты
            $paymentUrl = $this->paymentHelper->createPaymentLink($order);

            // Переадресация на страницу оплаты через Inertia
            return response()->json([
                'redirect' => $paymentUrl
            ]);

        } catch (Exception $e) {
            // Логируем детальную информацию об ошибке
            Log::error('Ошибка при создании заказа', [
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'error' => 'Ошибка при создании заказа: ' . $e->getMessage()
            ], 422);
        }
    }
} 