<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\PaymentService;
use App\Services\Orders\YookassaPaymentService;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected YookassaPaymentService $yookassaPaymentService;
    protected DocumentJobService $documentJobService;
    protected TransitionService $transitionService;

    public function __construct(
        PaymentService $paymentService,
        YookassaPaymentService $yookassaPaymentService,
        DocumentJobService $documentJobService,
        TransitionService $transitionService
    ) {
        $this->paymentService = $paymentService;
        $this->yookassaPaymentService = $yookassaPaymentService;
        $this->documentJobService = $documentJobService;
        $this->transitionService = $transitionService;
    }

    /**
     * Создать платеж ЮКасса для заказа
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function createYookassaPayment(Request $request, int $orderId)
    {
        try {
            // Логируем детали запроса для отладки
            Log::info('Детали запроса createYookassaPayment', [
                'order_id' => $orderId,
                'wantsJson' => $request->wantsJson(),
                'expectsJson' => $request->expectsJson(),
                'ajax' => $request->ajax(),
                'accept_header' => $request->header('Accept'),
                'content_type' => $request->header('Content-Type'),
                'headers' => $request->headers->all()
            ]);

            $order = Order::with('document', 'user')->find($orderId);

            if (!$order) {
                throw new Exception('Заказ не найден');
            }

            // Проверяем, что пользователь может оплачивать этот заказ
            if ($order->user_id !== Auth::id()) {
                throw new Exception('Нет доступа к этому заказу');
            }

            // Создаем платеж в ЮКасса
            $paymentResult = $this->yookassaPaymentService->createPayment($order);

            if ($paymentResult['success']) {
                Log::info('Перенаправление на оплату ЮКасса', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentResult['payment_id'],
                    'user_id' => Auth::id(),
                    'will_return_json' => $request->wantsJson()
                ]);

                // Если это AJAX запрос, возвращаем JSON
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $paymentResult['confirmation_url'],
                        'payment_id' => $paymentResult['payment_id']
                    ]);
                }

                // Перенаправляем на страницу оплаты ЮКасса
                return redirect($paymentResult['confirmation_url']);
            } else {
                throw new Exception('Ошибка при создании платежа');
            }

        } catch (Exception $e) {
            Log::error('Ошибка создания платежа ЮКасса', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            $redirectRoute = $order && $order->document_id 
                ? route('documents.show', $order->document_id)
                : route('dashboard');

            return redirect($redirectRoute)
                ->with('error', 'Ошибка при создании платежа: ' . $e->getMessage());
        }
    }

    /**
     * Создать платеж ЮКасса для заказа (API версия, всегда возвращает JSON)
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function createYookassaPaymentApi(Request $request, int $orderId)
    {
        try {
            $order = Order::with('document', 'user')->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Заказ не найден'
                ], 404);
            }

            // Проверяем, что пользователь может оплачивать этот заказ
            if ($order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Нет доступа к этому заказу'
                ], 403);
            }

            // Создаем платеж в ЮКасса
            $paymentResult = $this->yookassaPaymentService->createPayment($order);

            if ($paymentResult['success']) {
                Log::info('API: Создан платеж ЮКасса', [
                    'order_id' => $order->id,
                    'payment_id' => $paymentResult['payment_id'],
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'payment_url' => $paymentResult['confirmation_url'],
                    'payment_id' => $paymentResult['payment_id']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Ошибка при создании платежа'
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('API: Ошибка создания платежа ЮКасса', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API метод для проверки статуса платежа
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatusApi(Request $request, int $orderId)
    {
        try {
            $order = Order::with('document')->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => 'Заказ не найден'
                ], 404);
            }

            // Проверяем, что пользователь может проверять этот заказ
            if ($order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Нет доступа к этому заказу'
                ], 403);
            }

            // Используем принудительную проверку статуса
            $paymentStatus = $this->forceCheckPaymentStatusFromYookassa($order);

            // Если платеж успешен и это платеж за документ, запускаем генерацию
            if ($paymentStatus === 'completed' && $order->document_id && $order->document) {
                $document = $order->document;
                
                // Проверяем, можно ли запустить полную генерацию
                if ($document->status->canStartFullGenerationWithReferences($document)) {
                    try {
                        // Запускаем полную генерацию автоматически
                        $this->documentJobService->startFullGeneration($document, $this->transitionService);
                        
                        Log::info('Автоматически запущена полная генерация после подтверждения оплаты', [
                            'document_id' => $document->id,
                            'order_id' => $order->id
                        ]);
                    } catch (Exception $e) {
                        Log::error('Ошибка при автоматическом запуске генерации после подтверждения оплаты', [
                            'document_id' => $document->id,
                            'order_id' => $order->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'status' => $paymentStatus,
                'order_id' => $orderId
            ]);

        } catch (Exception $e) {
            Log::error('API: Ошибка проверки статуса платежа', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработать завершение оплаты (возврат с ЮКасса)
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handlePaymentComplete(Request $request, int $orderId)
    {
        try {
            $order = Order::with('document')->find($orderId);

            if (!$order) {
                // Если это AJAX запрос для проверки статуса
                if ($request->has('check_only') && $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'status' => 'not_found'
                    ], 404);
                }
                throw new Exception('Заказ не найден');
            }

            // Перенаправляем на страницу ожидания оплаты
            return Inertia::render('payment/PaymentWaiting', [
                'orderId' => $order->id,
                'orderInfo' => [
                    'id' => $order->id,
                    'amount' => $order->amount
                ],
                'isDocument' => (bool) $order->document_id || (isset($order->order_data['source_document_id'])),
                'documentId' => $order->document_id ?: ($order->order_data['source_document_id'] ?? null)
            ]);

        } catch (Exception $e) {
            Log::error('Ошибка при обработке возврата с оплаты ЮКасса', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'request_params' => $request->all()
            ]);

            // Определяем куда перенаправить в случае ошибки
            $redirectRoute = $order && $order->document_id 
                ? route('documents.show', $order->document_id)
                : route('dashboard');

            return redirect($redirectRoute)
                ->with('error', 'Ошибка при обработке платежа: ' . $e->getMessage());
        }
    }

    /**
     * Проверить статус платежа по заказу
     *
     * @param Order $order
     * @return string
     */
    protected function checkPaymentStatus(Order $order): string
    {
        try {
            // Находим последний платеж по этому заказу
            $payment = $order->payments()
                ->whereJsonContains('payment_data->payment_method', 'yookassa')
                ->latest()
                ->first();

            if (!$payment) {
                return 'not_found';
            }

            $paymentData = $payment->payment_data;
            $yookassaPaymentId = $paymentData['yookassa_payment_id'] ?? null;

            if (!$yookassaPaymentId) {
                return 'not_found';
            }

            // Получаем актуальную информацию о платеже из ЮКасса
            $paymentInfo = $this->yookassaPaymentService->getPaymentInfo($yookassaPaymentId);

            // Обновляем локальную информацию о платеже
            $paymentData['yookassa_status'] = $paymentInfo['status'];
            $payment->update([
                'payment_data' => $paymentData
            ]);

            // Возвращаем статус
            switch ($paymentInfo['status']) {
                case 'succeeded':
                    return 'completed';
                case 'pending':
                case 'waiting_for_capture':
                    return 'pending';
                case 'canceled':
                    return 'failed';
                default:
                    return 'unknown';
            }

        } catch (Exception $e) {
            Log::error('Ошибка проверки статуса платежа', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return 'error';
        }
    }

    /**
     * Принудительно проверить статус платежа в ЮКасса и обработать успешную оплату
     *
     * @param Order $order
     * @return string
     */
    protected function forceCheckPaymentStatusFromYookassa(Order $order): string
    {
        try {
            // Находим последний платеж по этому заказу
            $payment = $order->payments()
                ->whereJsonContains('payment_data->payment_method', 'yookassa')
                ->latest()
                ->first();

            if (!$payment) {
                Log::warning('Платеж не найден для заказа', ['order_id' => $order->id]);
                return 'not_found';
            }

            $paymentData = $payment->payment_data;
            $yookassaPaymentId = $paymentData['yookassa_payment_id'] ?? null;

            if (!$yookassaPaymentId) {
                Log::warning('ID платежа ЮКасса не найден', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                return 'not_found';
            }

            // Получаем актуальную информацию о платеже из ЮКасса
            $paymentInfo = $this->yookassaPaymentService->getPaymentInfo($yookassaPaymentId);

            Log::info('Принудительная проверка статуса платежа ЮКасса', [
                'order_id' => $order->id,
                'payment_id' => $yookassaPaymentId,
                'yookassa_status' => $paymentInfo['status'],
                'local_status' => $payment->status
            ]);

            // Если платеж succeeded и локально еще не обработан
            if ($paymentInfo['status'] === 'succeeded' && $payment->status !== 'completed') {
                
                Log::info('Платеж succeeded в ЮКасса, но не обработан локально - принудительно обрабатываем', [
                    'order_id' => $order->id,
                    'payment_id' => $yookassaPaymentId
                ]);

                // Имитируем обработку webhook для этого платежа
                $webhookPaymentData = [
                    'id' => $yookassaPaymentId,
                    'status' => 'succeeded',
                    'amount' => [
                        'value' => $paymentInfo['amount'],
                        'currency' => $paymentInfo['currency'] ?? 'RUB'
                    ],
                    'metadata' => [
                        'order_id' => $order->id
                    ]
                ];

                // Вызываем обработку успешного платежа
                $this->yookassaPaymentService->forceHandleSuccessfulPayment($order, $webhookPaymentData);

                return 'completed';
            }

            // Обновляем локальную информацию о платеже в любом случае
            $paymentData['yookassa_status'] = $paymentInfo['status'];
            $payment->update([
                'payment_data' => $paymentData
            ]);

            // Возвращаем статус
            switch ($paymentInfo['status']) {
                case 'succeeded':
                    return 'completed';
                case 'pending':
                case 'waiting_for_capture':
                    return 'pending';
                case 'canceled':
                    return 'failed';
                default:
                    return 'unknown';
            }

        } catch (Exception $e) {
            Log::error('Ошибка принудительной проверки статуса платежа', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 'error';
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