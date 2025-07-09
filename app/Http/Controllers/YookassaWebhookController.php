<?php

namespace App\Http\Controllers;

use App\Services\Orders\YookassaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
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
        $httpBody = $request->getContent();
        $requestData = null;
        
        try {
            Log::info('Получен webhook от ЮКасса', [
                'content_type' => $request->header('Content-Type'),
                'content_length' => strlen($httpBody),
                'user_agent' => $request->header('User-Agent'),
                'ip' => $request->ip(),
                'headers' => [
                    'Authorization' => $request->header('Authorization'),
                    'WWW-Authenticate' => $request->header('WWW-Authenticate'),
                ]
            ]);

            // Декодируем JSON
            $requestData = json_decode($httpBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Ошибка декодирования JSON в webhook ЮКасса', [
                    'json_error' => json_last_error_msg(),
                    'body_length' => strlen($httpBody),
                    'body_preview' => substr($httpBody, 0, 500)
                ]);
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            Log::info('Webhook JSON успешно декодирован', [
                'event' => $requestData['event'] ?? 'unknown',
                'object_id' => $requestData['object']['id'] ?? 'unknown'
            ]);

            // Проверяем подпись webhook (если настроена)
            $signature = $request->header('Authorization', '');
            if (!$this->yookassaService->verifyWebhookSignature($httpBody, $signature)) {
                Log::warning('Неверная подпись webhook ЮКасса', [
                    'signature' => $signature,
                    'body_length' => strlen($httpBody),
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Обрабатываем webhook
            $result = $this->yookassaService->handleWebhook($requestData);

            if ($result) {
                Log::info('Webhook успешно обработан', [
                    'event' => $requestData['event'] ?? 'unknown',
                    'object_id' => $requestData['object']['id'] ?? 'unknown'
                ]);
                return response()->json(['status' => 'success']);
            } else {
                Log::warning('Webhook обработан с предупреждениями', [
                    'event' => $requestData['event'] ?? 'unknown',
                    'object_id' => $requestData['object']['id'] ?? 'unknown'
                ]);
                return response()->json(['error' => 'Processing failed'], 500);
            }

        } catch (Exception $e) {
            Log::error('Критическая ошибка в webhook ЮКасса', [
                'error' => $e->getMessage(),
                'request_data' => $requestData,
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