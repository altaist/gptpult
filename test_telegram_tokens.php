<?php

require_once __DIR__ . '/vendor/autoload.php';

// Подключаем Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Support\Facades\Log;

echo "🧪 Тестирование системы Telegram токенов\n";
echo "==========================================\n\n";

// 1. Создаем тестового пользователя или находим существующего
$testUser = User::where('email', 'like', '%@auto.user')->first();
if (!$testUser) {
    $testUser = User::create([
        'name' => 'Test User',
        'email' => 'test_' . time() . '@auto.user',
        'password' => bcrypt('password'),
        'balance_rub' => 100
    ]);
    echo "✅ Создан тестовый пользователь: {$testUser->email}\n";
} else {
    echo "✅ Используем существующего пользователя: {$testUser->email}\n";
}

$telegramService = new TelegramBotService();

echo "\n1. Тестирование генерации токена связки:\n";
echo "----------------------------------------\n";

// Генерируем токен связки
$linkToken = $telegramService->generateLinkToken($testUser);
$testUser->refresh(); // Обновляем данные

echo "• Токен связки: " . substr($linkToken, 0, 20) . "...\n";
echo "• Время истечения: " . $testUser->telegram_token_expires_at->format('Y-m-d H:i:s') . "\n";
echo "• Истекает через: " . $testUser->telegram_token_expires_at->diffForHumans() . "\n";

// Проверяем метод isTokenValid
$reflection = new ReflectionClass($telegramService);
$isTokenValidMethod = $reflection->getMethod('isTokenValid');
$isTokenValidMethod->setAccessible(true);

$isValid = $isTokenValidMethod->invoke($telegramService, $testUser);
echo "• Токен валиден: " . ($isValid ? "ДА ✅" : "НЕТ ❌") . "\n";

echo "\n2. Тестирование генерации токена авторизации:\n";
echo "---------------------------------------------\n";

// Генерируем токен авторизации
$authToken = $telegramService->generateAuthToken($testUser);
$testUser->refresh();

echo "• Токен авторизации: " . substr($authToken, 0, 20) . "...\n";
echo "• Префикс 'auth_': " . (str_starts_with($authToken, 'auth_') ? "ДА ✅" : "НЕТ ❌") . "\n";
echo "• Время истечения: " . $testUser->telegram_token_expires_at->format('Y-m-d H:i:s') . "\n";
echo "• Истекает через: " . $testUser->telegram_token_expires_at->diffForHumans() . "\n";

$isValid = $isTokenValidMethod->invoke($telegramService, $testUser);
echo "• Токен валиден: " . ($isValid ? "ДА ✅" : "НЕТ ❌") . "\n";

echo "\n3. Тестирование URL-ов бота:\n";
echo "----------------------------\n";

$linkUrl = $telegramService->getBotLinkUrl($linkToken);
$authUrl = $telegramService->getBotAuthUrl($authToken);

echo "• URL связки: {$linkUrl}\n";
echo "• URL авторизации: {$authUrl}\n";

echo "\n4. Тестирование истечения токена:\n";
echo "---------------------------------\n";

// Искусственно устанавливаем время истечения в прошлое
$testUser->update([
    'telegram_token_expires_at' => now()->subMinutes(1)
]);
$testUser->refresh();

echo "• Время истечения установлено в прошлое: " . $testUser->telegram_token_expires_at->format('Y-m-d H:i:s') . "\n";

$isValid = $isTokenValidMethod->invoke($telegramService, $testUser);
echo "• Токен валиден после истечения: " . ($isValid ? "ДА ❌" : "НЕТ ✅") . "\n";

// Проверяем, что токен был очищен
$testUser->refresh();
echo "• Токен очищен автоматически: " . (empty($testUser->telegram_link_token) ? "ДА ✅" : "НЕТ ❌") . "\n";
echo "• Время истечения очищено: " . (empty($testUser->telegram_token_expires_at) ? "ДА ✅" : "НЕТ ❌") . "\n";

echo "\n5. Тестирование клавиатуры:\n";
echo "---------------------------\n";

// Проверяем, что у пользователя есть auth_token
if (!$testUser->auth_token) {
    $testUser->update(['auth_token' => \Illuminate\Support\Str::random(32)]);
    $testUser->refresh();
}

$createKeyboardMethod = $reflection->getMethod('createLoginKeyboard');
$createKeyboardMethod->setAccessible(true);

$keyboard = $createKeyboardMethod->invoke($telegramService, $testUser);

echo "• Клавиатура создана: " . (isset($keyboard['inline_keyboard']) ? "ДА ✅" : "НЕТ ❌") . "\n";
echo "• Количество кнопок: " . count($keyboard['inline_keyboard']) . "\n";

// Проверяем типы кнопок
$hasWebApp = false;
$hasUrl = false;

foreach ($keyboard['inline_keyboard'] as $row) {
    foreach ($row as $button) {
        if (isset($button['web_app'])) $hasWebApp = true;
        if (isset($button['url'])) $hasUrl = true;
    }
}

echo "• Есть WebApp кнопки: " . ($hasWebApp ? "ДА ✅" : "НЕТ ❌") . "\n";
echo "• Есть URL кнопки: " . ($hasUrl ? "ДА ✅" : "НЕТ ❌") . "\n";

echo "\n✅ Тестирование завершено!\n";
echo "==========================\n";

// Очищаем тестовые данные
$testUser->update([
    'telegram_link_token' => null,
    'telegram_token_expires_at' => null,
]);

echo "🧹 Тестовые данные очищены\n"; 