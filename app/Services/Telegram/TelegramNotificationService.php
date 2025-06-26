<?php

namespace App\Services\Telegram;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramNotificationService
{
    private TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Отправить уведомление о создании документа
     */
    public function notifyDocumentCreated(Document $document): void
    {
        $user = $document->user;
        
        if (!$this->canSendNotification($user)) {
            return;
        }

        $message = "📝 <b>Новый документ создан!</b>\n\n" .
            "Название: {$document->title}\n" .
            "Тип: {$document->documentType->name}\n" .
            "Статус: Создание структуры...\n\n" .
            "⏳ Ожидайте завершения генерации";

        $this->sendNotificationWithMenu($user, $message, $document);
    }

    /**
     * Отправить уведомление о готовности структуры документа
     */
    public function notifyDocumentStructureReady(Document $document): void
    {
        $user = $document->user;
        
        if (!$this->canSendNotification($user)) {
            return;
        }

        $message = "✅ <b>Структура документа готова!</b>\n\n" .
            "📄 {$document->title}\n\n" .
            "Теперь вы можете:\n" .
            "• Просмотреть и отредактировать структуру\n" .
            "• Запустить полную генерацию содержания";

        $this->sendNotificationWithMenu($user, $message, $document);
    }

    /**
     * Отправить уведомление о готовности полного содержания
     */
    public function notifyDocumentContentReady(Document $document): void
    {
        $user = $document->user;
        
        if (!$this->canSendNotification($user)) {
            return;
        }

        $message = "🎉 <b>Документ полностью готов!</b>\n\n" .
            "📄 {$document->title}\n\n" .
            "✨ Содержание сгенерировано\n" .
            "📥 Можно скачать документ";

        $this->sendNotificationWithMenu($user, $message, $document);
    }

    /**
     * Отправить уведомление об ошибке генерации
     */
    public function notifyDocumentGenerationFailed(Document $document): void
    {
        $user = $document->user;
        
        if (!$this->canSendNotification($user)) {
            return;
        }

        $statusText = match($document->status->value) {
            'pre_generation_failed' => 'создании структуры',
            'full_generation_failed' => 'генерации содержания',
            default => 'обработке'
        };

        $message = "❌ <b>Ошибка при {$statusText}</b>\n\n" .
            "📄 {$document->title}\n\n" .
            "Попробуйте запустить генерацию заново или обратитесь в поддержку.";

        $this->sendNotificationWithMenu($user, $message, $document);
    }

    /**
     * Отправить файл документа пользователю
     */
    public function sendDocumentFile(Document $document): void
    {
        $user = $document->user;
        
        if (!$this->canSendNotification($user)) {
            return;
        }

        // Генерируем Word файл
        $wordContent = $this->generateWordDocument($document);
        
        if (!$wordContent) {
            Log::warning('Could not generate Word document', ['document_id' => $document->id]);
            return;
        }

        // Отправляем файл через Telegram
        $this->sendDocumentToTelegram($user->telegram_id, $wordContent, $document->title);
    }

    /**
     * Проверить, можно ли отправить уведомление пользователю
     */
    private function canSendNotification(User $user): bool
    {
        return $user->telegram_id && $user->telegram_linked_at;
    }

    /**
     * Отправить уведомление с меню действий
     */
    private function sendNotificationWithMenu(User $user, string $message, Document $document): void
    {
        // Формируем базовый URL
        $baseUrl = $this->getBaseUrl();
        $isHttps = str_starts_with($baseUrl, 'https://');

        // Создаем кнопки для документа
        if ($isHttps) {
            // Mini App кнопки для HTTPS
            $keyboard = [
                'inline_keyboard' => [[
                    [
                        'text' => '📄 Открыть документ',
                        'web_app' => ['url' => $baseUrl . '/documents/' . $document->id]
                    ]
                ]]
            ];
        } else {
            // Обычные URL для HTTP (dev)
            if (!$user->auth_token) {
                $user->update(['auth_token' => \Illuminate\Support\Str::random(32)]);
                $user->refresh();
            }
            
            $documentUrl = "{$baseUrl}/auto-login/{$user->auth_token}?redirect=" . urlencode('/documents/' . $document->id);
            $keyboard = [
                'inline_keyboard' => [[
                    [
                        'text' => '📄 Открыть документ',
                        'url' => $documentUrl
                    ]
                ]]
            ];
        }

        $this->telegramService->sendMessage($user->telegram_id, $message, $keyboard);
    }

    /**
     * Получить базовый URL приложения
     */
    private function getBaseUrl(): string
    {
        if (app()->environment('local')) {
            return config('services.telegram.test_app_url');
        }
        
        return config('app.url');
    }

    /**
     * Сгенерировать Word документ
     */
    private function generateWordDocument(Document $document): ?string
    {
        try {
            // Используем существующий сервис генерации Word
            $wordService = app(\App\Services\Documents\Files\WordDocumentService::class);
            $file = $wordService->generate($document);
            
            // Читаем содержимое файла
            return file_get_contents($file->getFullPath());
        } catch (\Exception $e) {
            Log::error('Failed to generate Word document for Telegram', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Отправить документ в Telegram
     */
    private function sendDocumentToTelegram(int $chatId, string $content, string $title): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $url = "https://api.telegram.org/bot{$botToken}/sendDocument";

            // Создаем временный файл
            $filename = \Illuminate\Support\Str::slug($title) . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'telegram_doc_');
            file_put_contents($tempFile, $content);

            // Отправляем через multipart
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $chatId,
                    'document' => new \CURLFile($tempFile, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', $filename),
                    'caption' => "📄 <b>{$title}</b>\n\nВаш документ готов!",
                    'parse_mode' => 'HTML'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Удаляем временный файл
            unlink($tempFile);

            if ($httpCode === 200) {
                Log::info('Document sent to Telegram successfully', [
                    'chat_id' => $chatId,
                    'filename' => $filename
                ]);
            } else {
                Log::error('Failed to send document to Telegram', [
                    'chat_id' => $chatId,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending document to Telegram', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }
} 