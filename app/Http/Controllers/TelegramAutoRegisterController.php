<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TelegramLinkToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TelegramAutoRegisterController extends Controller
{
    /**
     * Страница автоматической регистрации через Telegram
     */
    public function show(Request $request)
    {
        $telegramId = $request->get('telegram_id');
        $telegramUsername = $request->get('telegram_username');
        
        if (!$telegramId) {
            return redirect('/')->with('error', 'Неверные параметры для регистрации');
        }
        
        // Проверяем, не существует ли уже пользователь с таким Telegram ID
        $existingUser = User::where('telegram_id', $telegramId)->first();
        
        if ($existingUser) {
            // Пользователь уже существует, авторизуем его
            Auth::login($existingUser);
            return redirect('/lk')->with('success', 'Добро пожаловать обратно!');
        }
        
        return Inertia::render('auth/telegram-register', [
            'telegram_id' => $telegramId,
            'telegram_username' => $telegramUsername
        ]);
    }
    
    /**
     * Создать нового пользователя через Telegram
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'telegram_id' => 'required|string',
            'telegram_username' => 'nullable|string|max:255',
            'accept_terms' => 'required|accepted'
        ]);
        
        $telegramId = $request->telegram_id;
        $telegramUsername = $request->telegram_username;
        
        // Проверяем, не существует ли уже пользователь с таким Telegram ID
        $existingUser = User::where('telegram_id', $telegramId)->first();
        
        if ($existingUser) {
            Auth::login($existingUser);
            return redirect('/lk')->with('success', 'Добро пожаловать обратно!');
        }
        
        // Создаем нового пользователя
        $user = User::create([
            'name' => $request->name,
            'email' => 'telegram_' . $telegramId . '@auto.user',
            'password' => Hash::make(Str::random(32)),
            'telegram_id' => $telegramId,
            'telegram_username' => $telegramUsername,
            'telegram_connected_at' => now(),
            'role_id' => 0, // Обычный пользователь
            'status' => 1, // Активный
            'balance_rub' => 0,
            'person' => [
                'telegram' => [
                    'id' => $telegramId,
                    'username' => $telegramUsername,
                    'registered_via' => 'telegram_bot'
                ]
            ],
            'settings' => [],
            'statistics' => []
        ]);
        
        // Авторизуем пользователя
        Auth::login($user);
        
        // Отправляем уведомление в Telegram
        $this->notifySuccessfulRegistration($telegramId, $user->name);
        
        return redirect('/lk')->with('success', 'Аккаунт успешно создан и связан с Telegram!');
    }
    
    /**
     * Отправить уведомление об успешной регистрации
     */
    private function notifySuccessfulRegistration($telegramId, $userName): void
    {
        $botToken = config('telegram.bot_token');
        $appUrl = config('app.url');
        
        // Для локальной разработки используем продакшн URL
        $buttonUrl = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1') 
            ? 'https://gptpult.ru' 
            : $appUrl;
        
        if (!$botToken) {
            return;
        }
        
        $text = "🎉 <b>Аккаунт успешно создан!</b>\n\n";
        $text .= "Добро пожаловать в GPT PULT, <b>{$userName}</b>!\n\n";
        $text .= "✅ Ваш аккаунт создан и связан с Telegram\n";
        $text .= "🔔 Уведомления включены\n";
        $text .= "💰 Стартовый баланс: 0 ₽\n\n";
        $text .= "🚀 Начинайте создавать документы прямо сейчас!";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => $buttonUrl . '/lk'
                    ]
                ],
                [
                    [
                        'text' => '📝 Создать документ',
                        'url' => $buttonUrl . '/new'
                    ]
                ]
            ]
        ];
        
        $this->sendTelegramMessage($telegramId, $text, $keyboard);
    }
    
    /**
     * Отправить сообщение в Telegram
     */
    private function sendTelegramMessage($chatId, $text, $keyboard = null): void
    {
        $botToken = config('telegram.bot_token');
        
        if (!$botToken) {
            return;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        curl_close($ch);
    }
} 