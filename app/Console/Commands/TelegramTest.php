<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TelegramNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--user-id=} {--notification=} {--create-test-user} {--test-scenarios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирование функций телеграм бота';

    private $notificationService;

    public function __construct(TelegramNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Тестирование Telegram бота');
        $this->newLine();

        if ($this->option('create-test-user')) {
            $this->createTestUser();
            return 0;
        }

        if ($this->option('test-scenarios')) {
            $this->testMessageScenarios();
            return 0;
        }

        $userId = $this->option('user-id');
        $notification = $this->option('notification');

        if (!$userId) {
            $this->showMenu();
            return 0;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ Пользователь с ID {$userId} не найден");
            return 1;
        }

        if (!$user->telegram_id) {
            $this->error("❌ У пользователя {$user->name} нет привязанного Telegram");
            $this->info("💡 Telegram ID: не установлен");
            return 1;
        }

        $this->info("👤 Пользователь: {$user->name}");
        $this->info("📱 Telegram ID: {$user->telegram_id}");
        $this->info("📅 Подключен: " . $user->telegram_connected_at->format('d.m.Y H:i'));
        $this->newLine();

        if ($notification) {
            $this->testNotification($user, $notification);
        } else {
            $this->testAllNotifications($user);
        }

        return 0;
    }

    /**
     * Показать интерактивное меню
     */
    private function showMenu()
    {
        $this->info('📋 Выберите действие:');
        $this->info('1. Создать тестового пользователя');
        $this->info('2. Протестировать уведомления для пользователя');
        $this->info('3. Показать статус бота');
        $this->info('4. Показать пользователей с Telegram');
        $this->info('5. Протестировать сценарии сообщений');
        $this->newLine();

        $choice = $this->ask('Введите номер действия (1-5)');

        switch ($choice) {
            case '1':
                $this->createTestUser();
                break;
            case '2':
                $this->selectUserForTesting();
                break;
            case '3':
                $this->showBotStatus();
                break;
            case '4':
                $this->showTelegramUsers();
                break;
            case '5':
                $this->testMessageScenarios();
                break;
            default:
                $this->error('❌ Неверный выбор');
        }
    }

    /**
     * Создать тестового пользователя
     */
    private function createTestUser()
    {
        $this->info('👤 Создание тестового пользователя...');

        $telegramId = $this->ask('Введите ваш Telegram ID для тестирования');
        $telegramUsername = $this->ask('Введите ваш Telegram username (без @)', 'testuser');

        $user = User::create([
            'name' => 'Тестовый Пользователь',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'telegram_id' => $telegramId,
            'telegram_username' => $telegramUsername,
            'telegram_connected_at' => now(),
            'balance_rub' => 1000
        ]);

        $this->info("✅ Тестовый пользователь создан:");
        $this->info("   ID: {$user->id}");
        $this->info("   Имя: {$user->name}");
        $this->info("   Email: {$user->email}");
        $this->info("   Telegram ID: {$user->telegram_id}");
        $this->info("   Баланс: {$user->balance_rub} ₽");

        if ($this->confirm('🧪 Протестировать уведомления для этого пользователя?')) {
            $this->testAllNotifications($user);
        }
    }

    /**
     * Выбрать пользователя для тестирования
     */
    private function selectUserForTesting()
    {
        $users = User::whereNotNull('telegram_id')->get();

        if ($users->isEmpty()) {
            $this->error('❌ Нет пользователей с привязанным Telegram');
            return;
        }

        $this->info('📱 Пользователи с Telegram:');
        foreach ($users as $user) {
            $this->info("   {$user->id}. {$user->name} (@{$user->telegram_username}) - ID: {$user->telegram_id}");
        }

        $userId = $this->ask('Введите ID пользователя для тестирования');
        $user = $users->find($userId);

        if (!$user) {
            $this->error('❌ Пользователь не найден');
            return;
        }

        $this->testAllNotifications($user);
    }

    /**
     * Показать статус бота
     */
    private function showBotStatus()
    {
        $botToken = config('telegram.bot_token');
        $botUsername = config('telegram.bot_username');

        if (!$botToken) {
            $this->error('❌ TELEGRAM_BOT_TOKEN не настроен');
            return;
        }

        $this->info('🤖 Статус бота:');
        $this->info("   Token: " . substr($botToken, 0, 10) . "...");
        $this->info("   Username: @{$botUsername}");
        $this->info("   Ссылка: https://t.me/{$botUsername}");
    }

    /**
     * Показать пользователей с Telegram
     */
    private function showTelegramUsers()
    {
        $users = User::whereNotNull('telegram_id')->get();

        $this->info("👥 Пользователи с Telegram ({$users->count()}):");
        $this->newLine();

        foreach ($users as $user) {
            $this->info("📱 {$user->name} ({$user->email})");
            $this->info("   ID: {$user->id}");
            $this->info("   Telegram ID: {$user->telegram_id}");
            $this->info("   Username: @{$user->telegram_username}");
            $this->info("   Подключен: " . $user->telegram_connected_at->format('d.m.Y H:i'));
            $this->info("   Баланс: " . number_format($user->balance_rub, 0, ',', ' ') . " ₽");
            $this->newLine();
        }
    }

    /**
     * Тестировать все уведомления для пользователя
     */
    private function testAllNotifications(User $user)
    {
        $this->info("🧪 Тестирование уведомлений для {$user->name}...");
        $this->newLine();

        $notifications = [
            'document_started' => 'Начало генерации документа',
            'document_ready' => 'Готовность документа',
            'document_error' => 'Ошибка генерации документа',
            'balance_topup' => 'Пополнение баланса'
        ];

        foreach ($notifications as $type => $description) {
            if ($this->confirm("📨 Отправить уведомление: {$description}?", true)) {
                $this->testNotification($user, $type);
                sleep(1);
            }
        }

        $this->info('✅ Тестирование завершено');
    }

    /**
     * Тестировать конкретное уведомление
     */
    private function testNotification(User $user, string $type)
    {
        $this->info("📤 Отправляем уведомление: {$type}");

        try {
            switch ($type) {
                case 'document_started':
                    $this->notificationService->notifyDocumentStarted(
                        $user, 
                        'Тестовый документ для проверки уведомлений'
                    );
                    break;

                case 'document_ready':
                    $this->notificationService->notifyDocumentReady(
                        $user, 
                        'Тестовый документ для проверки уведомлений',
                        999
                    );
                    break;

                case 'document_error':
                    $this->notificationService->notifyDocumentError(
                        $user, 
                        'Тестовый документ для проверки уведомлений',
                        999
                    );
                    break;

                case 'balance_topup':
                    $this->notificationService->notifyBalanceTopUp($user, 500);
                    break;

                default:
                    $this->error("❌ Неизвестный тип уведомления: {$type}");
                    return;
            }

            $this->info("✅ Уведомление отправлено");

        } catch (\Exception $e) {
            $this->error("❌ Ошибка отправки: " . $e->getMessage());
            Log::error("Telegram test notification error", [
                'type' => $type,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Тестирование различных сценариев сообщений
     */
    private function testMessageScenarios()
    {
        $this->info('🎭 Тестирование сценариев сообщений');
        $this->newLine();

        $scenarios = [
            [
                'name' => '/start без параметров (новый пользователь)',
                'description' => 'Должен показать общее приветственное сообщение'
            ],
            [
                'name' => '/start без параметров (существующий пользователь)',
                'description' => 'Должен показать персонализированное приветствие'
            ],
            [
                'name' => '/start с корректным ID',
                'description' => 'Должен связать аккаунт или показать что уже связан'
            ],
            [
                'name' => '/start с некорректным параметром',
                'description' => 'Должен показать ошибку неверного параметра'
            ],
            [
                'name' => 'Текстовое сообщение от связанного пользователя',
                'description' => 'Должен показать меню с кнопками ЛК и поддержки'
            ],
            [
                'name' => 'Текстовое сообщение от несвязанного пользователя',
                'description' => 'Должен предложить связать аккаунт'
            ]
        ];

        $this->info('📖 Доступные сценарии для тестирования:');
        $this->newLine();

        foreach ($scenarios as $index => $scenario) {
            $this->info(($index + 1) . ". " . $scenario['name']);
            $this->info("   💡 " . $scenario['description']);
            $this->newLine();
        }

        $this->info('🔧 Для тестирования используйте:');
        $this->info('• Локальный запуск: ./scripts/telegram-local.sh start');
        $this->info('• Тестовый режим: ./scripts/telegram-local.sh test');
        $this->newLine();

        $this->info('💡 В тестовом режиме бот автоматически симулирует различные сценарии');
        $this->info('   и покажет как обрабатывается каждый тип сообщения');
    }
} 