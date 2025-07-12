<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\PaymentService;
use App\Services\Orders\YookassaPaymentService;
use App\Services\Documents\DocumentJobService;
use App\Services\Orders\TransitionService;
use App\Enums\OrderStatus;
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
                    'confirmation_url' => $paymentResult['confirmation_url'],
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

            // Обновляем данные заказа после проверки статуса
            $order->refresh();

            // Если платеж успешен и это платеж за документ, запускаем генерацию
            if ($paymentStatus === 'completed' && $order->document_id && $order->document) {
                $document = $order->document;
                
                Log::info('Проверяем возможность автозапуска генерации после оплаты', [
                    'document_id' => $document->id,
                    'order_id' => $order->id,
                    'current_status' => $document->status->value,
                    'can_start' => $document->status->canStartFullGenerationWithReferences($document)
                ]);
                
                // Проверяем, можно ли запустить полную генерацию
                if ($document->status->canStartFullGenerationWithReferences($document)) {
                    // Дополнительная проверка - не генерируется ли уже
                    if (in_array($document->status->value, ['full_generating', 'full_generated'])) {
                        Log::info('Автозапуск отменен - документ уже генерируется или готов', [
                            'document_id' => $document->id,
                            'order_id' => $order->id,
                            'status' => $document->status->value
                        ]);
                    } else {
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
                } else {
                    Log::info('Автозапуск невозможен - документ не готов', [
                        'document_id' => $document->id,
                        'order_id' => $order->id,
                        'status' => $document->status->value
                    ]);
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
     * Обработать возврат с ЮКассы после оплаты
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handlePaymentComplete(Request $request, int $orderId)
    {
        try {
            $order = Order::with('document', 'user')->find($orderId);

            if (!$order) {
                Log::error('Заказ не найден при возврате с ЮКассы', [
                    'order_id' => $orderId,
                    'request_params' => $request->all()
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Заказ не найден');
            }

            // Проверяем права доступа
            if ($order->user_id !== Auth::id()) {
                Log::warning('Попытка доступа к чужому заказу при возврате с ЮКассы', [
                    'order_id' => $orderId,
                    'order_user_id' => $order->user_id,
                    'current_user_id' => Auth::id()
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Нет доступа к этому заказу');
            }

            Log::info('Возврат с ЮКассы', [
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'request_params' => $request->all()
            ]);

            // Небольшая задержка для обработки возможного вебхука
            // Это позволяет вебхуку обработаться первым и избежать дублирования
            Log::info('Ожидание перед проверкой статуса платежа', [
                'order_id' => $orderId,
                'delay_seconds' => 3
            ]);
            sleep(3);

            // Получаем последний платеж для этого заказа
            $payment = $order->payments()
                ->whereJsonContains('payment_data->payment_method', 'yookassa')
                ->latest()
                ->first();

            if (!$payment) {
                Log::warning('Платеж не найден при возврате с ЮКассы', [
                    'order_id' => $orderId
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Информация о платеже не найдена');
            }

            // Получаем ID платежа ЮКассы
            $yookassaPaymentId = $payment->payment_data['yookassa_payment_id'] ?? null;
            if (!$yookassaPaymentId) {
                Log::error('ID платежа ЮКассы не найден', [
                    'order_id' => $orderId,
                    'payment_id' => $payment->id
                ]);
                
                return redirect()->route('dashboard')
                    ->with('error', 'Ошибка при обработке платежа');
            }

            // Принудительно проверяем статус платежа в ЮКассе
            try {
                $paymentInfo = $this->yookassaPaymentService->getPaymentInfo($yookassaPaymentId);
                
                Log::info('Информация о платеже получена из ЮКассы', [
                    'order_id' => $orderId,
                    'payment_id' => $yookassaPaymentId,
                    'status' => $paymentInfo['status'],
                    'amount' => $paymentInfo['amount']
                ]);

                // Если платеж успешен, обрабатываем его принудительно
                if ($paymentInfo['status'] === 'succeeded') {
                    // Проверяем, не обработан ли уже заказ
                    $order->refresh(); // Обновляем данные заказа из БД
                    
                    if ($order->status === OrderStatus::PAID) {
                        Log::info('Заказ уже оплачен при возврате с ЮКассы', [
                            'order_id' => $orderId,
                            'payment_id' => $paymentInfo['id']
                        ]);
                        
                        $redirectRoute = $order->document_id 
                            ? route('documents.show', $order->document_id)
                            : route('dashboard');
                            
                        return redirect($redirectRoute)
                            ->with('success', 'Платеж уже обработан! Средства зачислены на баланс.');
                    }
                    
                    $this->yookassaPaymentService->forceHandleSuccessfulPayment($order, $paymentInfo);
                    
                    $redirectRoute = $order->document_id 
                        ? route('documents.show', $order->document_id)
                        : route('dashboard');
                        
                    return redirect($redirectRoute)
                        ->with('success', 'Платеж успешно обработан! Средства зачислены на баланс.');
                }
                
                // Если платеж ожидает подтверждения
                if ($paymentInfo['status'] === 'waiting_for_capture') {
                    $redirectRoute = $order->document_id 
                        ? route('documents.show', $order->document_id)
                        : route('dashboard');
                        
                    return redirect($redirectRoute)
                        ->with('info', 'Платеж обрабатывается. Средства будут зачислены в течение нескольких минут.');
                }
                
                // Другие статусы
                $redirectRoute = $order->document_id 
                    ? route('documents.show', $order->document_id)
                    : route('dashboard');
                    
                return redirect($redirectRoute)
                    ->with('warning', 'Платеж находится в обработке. Статус: ' . $paymentInfo['status']);

            } catch (Exception $e) {
                Log::error('Ошибка при получении информации о платеже из ЮКассы', [
                    'order_id' => $orderId,
                    'payment_id' => $yookassaPaymentId,
                    'error' => $e->getMessage()
                ]);
                
                $redirectRoute = $order->document_id 
                ? route('documents.show', $order->document_id)
                : route('dashboard');

            return redirect($redirectRoute)
                    ->with('warning', 'Платеж обрабатывается. Проверьте статус через несколько минут.');
            }

        } catch (Exception $e) {
            Log::error('Критическая ошибка при обработке возврата с ЮКассы', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Произошла ошибка при обработке платежа. Обратитесь в службу поддержки.');
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
     * Принудительно проверить статус платежа из ЮКассы
     *
     * @param Order $order
     * @return array|null
     */
    private function forceCheckPaymentStatusFromYookassa(Order $order): ?array
    {
        try {
            // Получаем последний платеж ЮКассы для заказа
            $payment = $order->payments()
                ->whereJsonContains('payment_data->payment_method', 'yookassa')
                ->latest()
                ->first();

            if (!$payment) {
                return null;
            }

            $yookassaPaymentId = $payment->payment_data['yookassa_payment_id'] ?? null;
            if (!$yookassaPaymentId) {
                return null;
            }

            // Получаем актуальную информацию из ЮКассы
            $paymentInfo = $this->yookassaPaymentService->getPaymentInfo($yookassaPaymentId);

            // Если статус изменился на succeeded, обрабатываем платеж
            if ($paymentInfo['status'] === 'succeeded' && $payment->status !== 'completed') {
                $this->yookassaPaymentService->forceHandleSuccessfulPayment($order, $paymentInfo);
            }

            return $paymentInfo;

        } catch (Exception $e) {
            Log::error('Ошибка при принудительной проверке статуса платежа', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return null;
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