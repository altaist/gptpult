<?php

namespace App\Http\Controllers;

use App\Services\Orders\YookassaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationRefundSucceeded;
use YooKassa\Model\Notification\NotificationEventType;
use Exception;

class YookassaWebhookController extends Controller
{
    protected YookassaPaymentService $yookassaService;

    public function __construct(YookassaPaymentService $yookassaService)
    {
        $this->yookassaService = $yookassaService;
    }

    /**
     * Обработать webhook уведомление от ЮКасса
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Получаем данные из POST-запроса от ЮKassa, как в примере
            $source = file_get_contents('php://input');
            $requestBody = json_decode($source, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Ошибка декодирования JSON в webhook ЮКасса', [
                    'json_error' => json_last_error_msg(),
                    'body_length' => strlen($source),
                    'body_preview' => substr($source, 0, 500)
                ]);
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            Log::info('Получен webhook от ЮКасса', [
                'content_type' => $request->header('Content-Type'),
                'content_length' => strlen($source),
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
                'event' => $requestBody['event'] ?? 'unknown',
                'object_id' => $requestBody['object']['id'] ?? 'unknown'
            ]);

            // Создаем объект класса уведомлений в зависимости от события
            $notification = null;
            
            switch ($requestBody['event']) {
                case NotificationEventType::PAYMENT_SUCCEEDED:
                    $notification = new NotificationSucceeded($requestBody);
                    break;
                    
                case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                    $notification = new NotificationWaitingForCapture($requestBody);
                    break;
                    
                case NotificationEventType::PAYMENT_CANCELED:
                    $notification = new NotificationCanceled($requestBody);
                    break;
                    
                case NotificationEventType::REFUND_SUCCEEDED:
                    $notification = new NotificationRefundSucceeded($requestBody);
                    break;
                    
                default:
                    Log::warning('Неизвестный тип события webhook', [
                        'event' => $requestBody['event'] ?? 'unknown'
                    ]);
                    return response()->json(['error' => 'Unknown event type'], 400);
            }

            // Получаем объект платежа
            $payment = $notification->getObject();

            Log::info('Webhook успешно декодирован через объект уведомления', [
                'event' => $requestBody['event'],
                'payment_id' => $payment->getId(),
                'payment_status' => $payment->getStatus(),
                'amount' => $payment->getAmount()->getValue()
            ]);

            // Преобразуем объект платежа в массив для совместимости с существующим кодом
            $paymentArray = [
                'id' => $payment->getId(),
                'status' => $payment->getStatus(),
                'amount' => [
                    'value' => $payment->getAmount()->getValue(),
                    'currency' => $payment->getAmount()->getCurrency()
                ],
                'metadata' => $payment->getMetadata() ? $payment->getMetadata()->toArray() : [],
                'created_at' => $payment->getCreatedAt() ? $payment->getCreatedAt()->format('c') : null,
                'captured_at' => $payment->getCapturedAt() ? $payment->getCapturedAt()->format('c') : null,
                'expires_at' => $payment->getExpiresAt() ? $payment->getExpiresAt()->format('c') : null
            ];

            // Обрабатываем webhook через существующий сервис
            $webhookData = [
                'event' => $requestBody['event'],
                'object' => $paymentArray
            ];

            $result = $this->yookassaService->handleWebhook($webhookData);

            if ($result) {
                Log::info('Webhook успешно обработан', [
                    'event' => $requestBody['event'],
                    'payment_id' => $payment->getId()
                ]);
                return response()->json(['status' => 'success']);
            } else {
                Log::warning('Webhook обработан с предупреждениями', [
                    'event' => $requestBody['event'],
                    'payment_id' => $payment->getId()
                ]);
                return response()->json(['error' => 'Processing failed'], 500);
            }

        } catch (Exception $e) {
            Log::error('Критическая ошибка в webhook ЮКасса', [
                'error' => $e->getMessage(),
                'request_body' => $requestBody ?? null,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip()
            ]);

            // Возвращаем 500 ошибку, чтобы ЮКасса повторила отправку
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
} 