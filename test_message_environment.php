<?php

require_once __DIR__ . '/vendor/autoload.php';

// Подключаем Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Тестирование отображения переноса документов по окружениям\n";
echo "============================================================\n\n";

// Симуляция логики из handleTelegramAuth
$documentsTransferred = 3;
$finalUserName = "Тестовый Пользователь";

echo "Количество перенесенных документов: {$documentsTransferred}\n";
echo "Текущее окружение: " . app()->environment() . "\n";
echo "Debug режим: " . (config('app.debug') ? 'ВКЛ' : 'ВЫКЛ') . "\n\n";

// Тестируем разные окружения
$environments = ['local', 'testing', 'production'];
$debugModes = [true, false];

foreach ($environments as $env) {
    foreach ($debugModes as $debug) {
        echo "=== Окружение: {$env}, Debug: " . ($debug ? 'ВКЛ' : 'ВЫКЛ') . " ===\n";
        
        // Симулируем условие из кода
        $shouldShowTransfer = $documentsTransferred > 0 && 
                             (in_array($env, ['local', 'testing']) || $debug);
        
        $messageText = "✅ Авторизация через Telegram успешна!\n\n" .
                       "Добро пожаловать, {$finalUserName}!\n\n";
                       
        if ($shouldShowTransfer) {
            $messageText .= "📄 Перенесено документов: {$documentsTransferred}\n\n";
        }
        
        $messageText .= "Ваш аккаунт теперь связан с Telegram.";
        
        echo "Показывать перенос: " . ($shouldShowTransfer ? "ДА ✅" : "НЕТ ❌") . "\n";
        echo "Сообщение:\n{$messageText}\n\n";
    }
}

echo "✅ Текущее реальное окружение:\n";
echo "Окружение: " . app()->environment() . "\n";
echo "Debug: " . (config('app.debug') ? 'ВКЛ' : 'ВЫКЛ') . "\n";

$realCondition = $documentsTransferred > 0 && 
                 (app()->environment(['local', 'testing']) || config('app.debug'));
                 
echo "Будет показан перенос документов: " . ($realCondition ? "ДА ✅" : "НЕТ ❌") . "\n"; 