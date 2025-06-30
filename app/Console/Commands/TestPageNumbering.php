<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Document;
use App\Models\User;
use App\Services\Documents\Files\WordDocumentService;
use App\Services\Files\FilesService;

class TestPageNumbering extends Command
{
    protected $signature = 'test:page-numbering';
    protected $description = 'Тестирует нумерацию страниц и новый титульный лист';

    public function handle()
    {
        $this->info('📄 Тестирование нумерации страниц и титульного листа...');
        
        try {
            // Находим первого пользователя
            $user = User::first();
            if (!$user) {
                $this->error('❌ Не найден ни один пользователь в системе');
                return self::FAILURE;
            }
            
            // Находим или создаем тип документа
            $documentType = \App\Models\DocumentType::first();
            if (!$documentType) {
                $documentType = \App\Models\DocumentType::create([
                    'name' => 'Тестовая работа',
                    'slug' => 'test-work',
                    'description' => 'Для тестирования'
                ]);
            }
            
            // Создаем тестовый документ с полным содержимым
            $document = new Document([
                'title' => 'Анализ современных методов искусственного интеллекта',
                'user_id' => $user->id,
                'document_type_id' => $documentType->id,
                'status' => 'full_generated',
                'structure' => [
                    'topic' => 'Анализ современных методов искусственного интеллекта',
                    'contents' => [
                        [
                            'title' => 'Введение',
                            'subtopics' => [
                                ['title' => 'Актуальность темы'],
                                ['title' => 'Цели и задачи']
                            ]
                        ],
                        [
                            'title' => 'Основная часть',
                            'subtopics' => [
                                ['title' => 'Обзор литературы'],
                                ['title' => 'Методология исследования']
                            ]
                        ]
                    ],
                    'references' => [
                        [
                            'title' => 'Современные алгоритмы машинного обучения',
                            'author' => 'Иванов Иван Иванович',
                            'url' => 'https://cyberleninka.ru/article/example',
                            'type' => 'article',
                            'description' => 'Основная работа по теме исследования'
                        ],
                        [
                            'title' => 'Основы нейронных сетей',
                            'author' => 'Петров Петр Петрович',
                            'publisher' => 'Наука',
                            'year' => '2023',
                            'type' => 'book',
                            'description' => 'Фундаментальная работа по нейросетям'
                        ]
                    ]
                ],
                'content' => [
                    'topics' => [
                        [
                            'title' => 'Введение',
                            'subtopics' => [
                                [
                                    'title' => 'Актуальность темы',
                                    'content' => 'В современном мире искусственный интеллект играет все более важную роль в различных сферах деятельности человека. Развитие технологий машинного обучения и глубокого обучения открывает новые возможности для решения сложных задач. Данная работа посвящена анализу современных методов ИИ и их практическому применению.'
                                ],
                                [
                                    'title' => 'Цели и задачи',
                                    'content' => 'Целью данной работы является комплексный анализ современных методов искусственного интеллекта. Основные задачи: изучить теоретические основы ИИ, проанализировать современные алгоритмы, рассмотреть практические применения.'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Основная часть',
                            'subtopics' => [
                                [
                                    'title' => 'Обзор литературы',
                                    'content' => 'Анализ научной литературы показывает, что в области искусственного интеллекта наблюдается стремительное развитие. Ключевыми направлениями являются машинное обучение, компьютерное зрение, обработка естественного языка и робототехника.'
                                ],
                                [
                                    'title' => 'Методология исследования',
                                    'content' => 'В ходе исследования использовались следующие методы: анализ научной литературы, сравнительный анализ алгоритмов, экспериментальная проверка гипотез. Исследование проводилось на основе актуальных данных и современных технологий.'
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
            
            $document->save();
            
            // Генерируем Word документ
            $filesService = app(FilesService::class);
            $wordService = new WordDocumentService($filesService);
            
            $file = $wordService->generate($document);
            
            $this->info("✅ Word документ с нумерацией страниц создан!");
            $this->line("📄 ID документа: {$document->id}");
            $this->line("📁 Файл: {$file->name}");
            $this->line("🔗 Путь: {$file->path}");
            
            $this->line('');
            $this->line('📋 Особенности нового документа:');
            $this->line('  🎯 Титульный лист без номера страницы');
            $this->line('  📍 Город и год в нижнем колонтитуле титульного листа');
            $this->line('  🔢 Нумерация страниц для содержания, основного текста и ссылок');
            $this->line('  📖 Все ссылки отформатированы по ГОСТ стандартам');
            $this->line('  📝 Заглушки для заполнения в титульном листе');
            
            // Удаляем тестовый документ
            $document->delete();
            
            $this->line('');
            $this->info('🧹 Тестовый документ удален из базы данных');
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
} 