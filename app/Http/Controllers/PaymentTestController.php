<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\YookassaPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Exception;

class PaymentTestController extends Controller
{
    protected YookassaPaymentService $yookassaService;

    public function __construct(YookassaPaymentService $yookassaService)
    {
        $this->yookassaService = $yookassaService;
    }

    /**
     * Отобразить тестовую страницу оплаты
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function show()
    {
        $yookassaConfigured = $this->isYookassaConfigured();
        $configurationError = null;
        
        // Если ЮКасса не настроена, пробуем получить детали ошибки
        if (!$yookassaConfigured) {
            $configurationError = $this->getConfigurationError();
        }

        return Inertia::render('payment/PaymentTest', [
            'yookassaConfigured' => $yookassaConfigured,
            'configurationError' => $configurationError,
            'user' => Auth::user()
        ]);
    }

    /**
     * Создать тестовый заказ
     */
    public function createTestOrder(Request $request)
    {
        try {
            // Проверяем конфигурацию ЮКасса перед созданием заказа
            if (!$this->isYookassaConfigured()) {
                return response()->json([
                    'success' => false,
                    'error' => $this->getConfigurationError()
                ], 400);
            }

            $request->validate([
                'amount' => 'required|numeric|min:1|max:10000'
            ]);

            $order = Order::create([
                'user_id' => Auth::id(),
                'amount' => $request->amount,
                'status' => \App\Enums\OrderStatus::NEW,
                'order_data' => [
                    'test_order' => true,
                    'description' => 'Тестовый заказ для проверки ЮКасса',
                    'created_via' => 'test_page'
                ]
            ]);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'message' => 'Тестовый заказ создан'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверить, настроена ли ЮКасса
     */
    protected function isYookassaConfigured(): bool
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');
        
        return !empty($shopId) && !empty($secretKey);
    }
    
    /**
     * Получить детали ошибки конфигурации
     */
    protected function getConfigurationError(): string
    {
        $shopId = config('services.yookassa.shop_id');
        $secretKey = config('services.yookassa.secret_key');
        
        if (empty($shopId) && empty($secretKey)) {
            return 'Не настроены YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env файле';
        } elseif (empty($shopId)) {
            return 'Не настроена переменная YOOKASSA_SHOP_ID в .env файле';
        } elseif (empty($secretKey)) {
            return 'Не настроена переменная YOOKASSA_SECRET_KEY в .env файле';
        }
        
        return 'Неизвестная ошибка конфигурации ЮКасса';
    }
} 