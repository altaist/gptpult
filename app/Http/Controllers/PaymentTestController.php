<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentTestController extends Controller
{
    /**
     * Отобразить тестовую страницу оплаты
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function show(Request $request)
    {
        return Inertia::render('payment/PaymentTest', [
            'order_id' => $request->get('order_id'),
            'amount' => $request->get('amount'),
            'description' => $request->get('description', 'Тестовый платеж')
        ]);
    }
} 