<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    /**
     * Отправить уведомление о готовности документа
     */
    public function notifyDocumentReady(User $user, $documentTitle, $documentId): void
    {
        if (!$user->telegram_id) {
            Log::info("User {$user->id} doesn't have Telegram connected, skipping notification");
            return;
        }

        $text = "📄 <b>Документ готов!</b>\n\n";
        $text .= "Ваш документ \"<b>{$documentTitle}</b>\" успешно сгенерирован.\n\n";
        $text .= "✅ Документ готов к скачиванию\n";
        $text .= "🔗 Переходите в личный кабинет для просмотра";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '📄 Открыть документ',
                        'url' => "https://gptpult.ru/documents/{$documentId}"
                    ]
                ],
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => 'https://gptpult.ru/lk'
                    ]
                ]
            ]
        ];

        $this->sendTelegramMessage($user->telegram_id, $text, $keyboard);
    }

    /**
     * Отправить уведомление об ошибке генерации
     */
    public function notifyDocumentError(User $user, $documentTitle, $documentId): void
    {
        if (!$user->telegram_id) {
            return;
        }

        $text = "❌ <b>Ошибка генерации</b>\n\n";
        $text .= "При генерации документа \"<b>{$documentTitle}</b>\" произошла ошибка.\n\n";
        $text .= "Попробуйте еще раз или обратитесь в поддержку.";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🔄 Попробовать снова',
                        'url' => "https://gptpult.ru/documents/{$documentId}"
                    ]
                ],
                [
                    [
                        'text' => '💬 Поддержка',
                        'url' => 'https://gptpult.ru/support'
                    ]
                ]
            ]
        ];

        $this->sendTelegramMessage($user->telegram_id, $text, $keyboard);
    }

    /**
     * Отправить уведомление о начале генерации
     */
    public function notifyDocumentStarted(User $user, $documentTitle): void
    {
        if (!$user->telegram_id) {
            return;
        }

        $text = "🚀 <b>Генерация начата</b>\n\n";
        $text .= "Начата генерация документа \"<b>{$documentTitle}</b>\".\n\n";
        $text .= "⏳ Обычно процесс занимает 2-5 минут\n";
        $text .= "🔔 Мы уведомим вас о готовности";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => 'https://gptpult.ru/lk'
                    ]
                ]
            ]
        ];

        $this->sendTelegramMessage($user->telegram_id, $text, $keyboard);
    }

    /**
     * Отправить уведомление о пополнении баланса
     */
    public function notifyBalanceTopUp(User $user, $amount): void
    {
        if (!$user->telegram_id) {
            return;
        }

        $text = "💰 <b>Баланс пополнен</b>\n\n";
        $text .= "Ваш баланс пополнен на <b>{$amount} ₽</b>\n\n";
        $text .= "💳 Текущий баланс: <b>" . number_format($user->balance_rub, 0, ',', ' ') . " ₽</b>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🏠 Личный кабинет',
                        'url' => 'https://gptpult.ru/lk'
                    ]
                ]
            ]
        ];

        $this->sendTelegramMessage($user->telegram_id, $text, $keyboard);
    }

    /**
     * Отправить сообщение в телеграм
     */
    private function sendTelegramMessage($chatId, $text, $keyboard = null): void
    {
        $botToken = config('telegram.bot_token');

        if (!$botToken) {
            Log::warning('Telegram bot token not configured');
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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            Log::info("Telegram notification sent to user {$chatId}");
        } else {
            Log::error("Failed to send Telegram notification to user {$chatId}", [
                'http_code' => $httpCode,
                'response' => $response
            ]);
        }
    }
} 