<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Enums\DocumentStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Убеждаемся, что есть пользователи и типы документов
        if (User::count() === 0) {
            User::factory()->create([
                'name' => 'Тестовый пользователь',
                'email' => 'test@example.com',
            ]);
        }

        if (DocumentType::count() === 0) {
            $this->call(DocumentTypeSeeder::class);
        }

        $user = User::first();
        $documentType = DocumentType::first();

        // Создаем один документ с ID 3278
        DB::table('documents')->insert([
            'id' => 3278,
            'user_id' => $user->id,
            'document_type_id' => $documentType->id,
            'title' => 'Анализ современных тенденций в цифровой трансформации',
            'structure' => json_encode([
                'topic' => 'Цифровая трансформация в современном мире',
                'theses' => 'Исследование влияния цифровых технологий на различные сферы жизни',
                'objectives' => [
                    'Изучить теоретические основы цифровой трансформации',
                    'Проанализировать практические аспекты внедрения',
                    'Сформулировать выводы и рекомендации',
                ],
                'contents' => [
                    [
                        'title' => 'Введение',
                        'subtopics' => [
                            ['title' => 'Актуальность темы', 'content' => 'Описание актуальности цифровой трансформации'],
                            ['title' => 'Цель и задачи', 'content' => 'Формулировка целей исследования'],
                        ],
                    ],
                    [
                        'title' => 'Теоретическая часть',
                        'subtopics' => [
                            ['title' => 'Литературный обзор', 'content' => 'Анализ современных источников'],
                            ['title' => 'Методологические основы', 'content' => 'Описание методов исследования'],
                        ],
                    ],
                ],
                'references' => [
                    [
                        'title' => 'Цифровая трансформация: вызовы и возможности',
                        'author' => 'Иванов И.И.',
                        'year' => '2023',
                        'url' => 'https://example.com/source1',
                    ],
                    [
                        'title' => 'Технологии будущего',
                        'author' => 'Петров П.П.',
                        'year' => '2022',
                        'url' => 'https://example.com/source2',
                    ],
                ],
            ]),
            'content' => json_encode([
                'introduction' => 'Полное введение к работе с подробным описанием актуальности и целей исследования.',
                'main_content' => 'Основное содержание работы с детальным анализом и исследованием.',
                'conclusion' => 'Заключение с выводами и рекомендациями по результатам работы.',
            ]),
            'pages_num' => 25,
            'gpt_settings' => json_encode([
                'service' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
            ]),
            'status' => DocumentStatus::FULL_GENERATED->value,
            'thread_id' => 'thread_' . uniqid(),
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(1),
        ]);

        // Сбрасываем AUTO_INCREMENT для следующих записей
        DB::statement("ALTER TABLE documents AUTO_INCREMENT = 3279");
        
        $this->command->info("Создан документ с ID 3278");
    }
} 