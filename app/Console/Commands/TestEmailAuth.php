<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Console\Command;

class TestEmailAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-auth {email : Email адрес для тестирования}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует отправку кода авторизации по email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный email адрес');
            return 1;
        }
        
        $this->info("Тестируем отправку кода на: {$email}");
        
        // Генерируем тестовый код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Ищем или создаем тестового пользователя
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->info('Пользователь не найден, создаём тестового...');
            $user = User::create([
                'name' => 'Тестовый пользователь',
                'email' => $email,
                'password' => bcrypt('password'),
            ]);
        }
        
        try {
            // Отправляем уведомление
            $user->notify(new EmailVerificationCode($code));
            
            $this->info("✅ Код {$code} успешно отправлен на {$email}");
            $this->info('Проверьте почтовый ящик или логи (если MAIL_MAILER=log)');
            
            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при отправке: " . $e->getMessage());
            return 1;
        }
    }
}
