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
        try {
            // Получаем данные запроса
            $httpBody = $request->getContent();
            $requestData = json_decode($httpBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Ошибка декодирования JSON в webhook ЮКасса', [
                    'json_error' => json_last_error_msg(),
                    'body' => $httpBody
                ]);
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            // Проверяем подпись (если настроена)
            $signature = $request->header('SHA1-Signature', '');
            if (!$this->yookassaService->verifyWebhookSignature($httpBody, $signature)) {
                Log::warning('Неверная подпись webhook ЮКасса', [
                    'signature' => $signature,
                    'body_length' => strlen($httpBody)
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            // Обрабатываем webhook
            $result = $this->yookassaService->handleWebhook($requestData);

            if ($result) {
                return response()->json(['status' => 'success']);
            } else {
                return response()->json(['error' => 'Processing failed'], 500);
            }

        } catch (Exception $e) {
            Log::error('Критическая ошибка в webhook ЮКасса', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $requestData ?? null
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
} 