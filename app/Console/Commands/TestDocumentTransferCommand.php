<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Document;
use App\Services\Documents\DocumentTransferService;
use Illuminate\Console\Command;

class TestDocumentTransferCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:document-transfer {--create-test-data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Тестирует перенос документов между пользователями';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Тестирование переноса документов');
        
        if ($this->option('create-test-data')) {
            $this->createTestData();
        }

        $transferService = new DocumentTransferService();

        // Показываем всех временных пользователей
        $tempUsers = User::where('email', 'like', '%@auto.user')->get();
        
        if ($tempUsers->isEmpty()) {
            $this->warn('❌ Нет временных пользователей (@auto.user)');
            return;
        }

        $this->info('📋 Найдено временных пользователей: ' . $tempUsers->count());
        
        foreach ($tempUsers as $tempUser) {
            $documentsCount = Document::where('user_id', $tempUser->id)->count();
            $this->line("   ID: {$tempUser->id}, Email: {$tempUser->email}, Документов: {$documentsCount}");
        }

        // Показываем всех постоянных пользователей
        $permanentUsers = User::where('email', 'not like', '%@auto.user')->get();
        
        if ($permanentUsers->isEmpty()) {
            $this->warn('❌ Нет постоянных пользователей');
            return;
        }

        $this->info('📋 Найдено постоянных пользователей: ' . $permanentUsers->count());
        
        foreach ($permanentUsers as $permanentUser) {
            $documentsCount = Document::where('user_id', $permanentUser->id)->count();
            $this->line("   ID: {$permanentUser->id}, Email: {$permanentUser->email}, Документов: {$documentsCount}");
        }

        // Выполняем тестовый перенос
        if ($this->confirm('Выполнить тестовый перенос документов?')) {
            $fromUser = $tempUsers->first();
            $toUser = $permanentUsers->first();
            
            $this->info("🔄 Переносим документы от пользователя {$fromUser->id} к пользователю {$toUser->id}");
            
            $result = $transferService->transferDocuments($fromUser, $toUser);
            
            if ($result['success']) {
                $this->info("✅ Успешно перенесено документов: {$result['transferred_count']}");
            } else {
                $this->error("❌ Ошибка переноса: {$result['message']}");
            }
        }
    }

    private function createTestData()
    {
        $this->info('📝 Создание тестовых данных...');
        
        // Создаем временного пользователя с документами
        $tempUser = User::firstOrCreate([
            'email' => 'test_temp@auto.user'
        ], [
            'name' => 'Временный Тестовый Пользователь',
            'password' => bcrypt('password'),
            'auth_token' => \Illuminate\Support\Str::random(32),
            'role_id' => 0,
            'status' => 1,
        ]);

        // Создаем постоянного пользователя
        $permanentUser = User::firstOrCreate([
            'email' => 'test_permanent@example.com'
        ], [
            'name' => 'Постоянный Тестовый Пользователь',
            'password' => bcrypt('password'),
            'auth_token' => \Illuminate\Support\Str::random(32),
            'role_id' => 0,
            'status' => 1,
        ]);

        // Создаем тестовые документы для временного пользователя
        if (Document::where('user_id', $tempUser->id)->count() === 0) {
            Document::factory()->count(3)->create([
                'user_id' => $tempUser->id
            ]);
            
            $this->info("📄 Создано 3 тестовых документа для временного пользователя");
        }

        $this->info("✅ Тестовые данные готовы");
    }
} 