<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_type_id' => 1,
            'title' => $this->faker->sentence(3),
            'structure' => [
                'topic' => $this->faker->sentence(5),
                'theses' => $this->faker->paragraphs(2, true),
                'objectives' => [
                    $this->faker->sentence(6),
                    $this->faker->sentence(7),
                    $this->faker->sentence(5),
                ],
                'contents' => [
                    [
                        'title' => $this->faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                        ],
                    ],
                    [
                        'title' => $this->faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                        ],
                    ],
                    [
                        'title' => $this->faker->sentence(3),
                        'subtopics' => [
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                            [
                                'title' => $this->faker->sentence(4),
                                'content' => $this->faker->paragraph(2),
                            ],
                        ],
                    ],
                ],
                'references' => [
                    [
                        'title' => $this->faker->sentence(4),
                        'author' => $this->faker->name,
                        'year' => $this->faker->year,
                        'url' => $this->faker->url,
                    ],
                    [
                        'title' => $this->faker->sentence(3),
                        'author' => $this->faker->name,
                        'year' => $this->faker->year,
                        'url' => $this->faker->url,
                    ],
                ],
            ],
            'gpt_settings' => [
                'service' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
            ],
            'status' => 'draft',
        ];
    }

    /**
     * Создать документ в состоянии генерации
     */
    public function preGenerating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pre_generating',
        ]);
    }

    /**
     * Создать документ с готовой структурой
     */
    public function preGenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pre_generated',
        ]);
    }

    /**
     * Создать документ в процессе полной генерации
     */
    public function fullGenerating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'full_generating',
        ]);
    }

    /**
     * Создать полностью сгенерированный документ
     */
    public function fullGenerated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'full_generated',
        ]);
    }

    /**
     * Создать документ на проверке
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_review',
        ]);
    }

    /**
     * Создать утвержденный документ
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Создать отклоненный документ
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Создать минимальный документ без фейковых данных
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'structure' => [
                'topic' => $attributes['title'] ?? 'Новый документ',
                'theses' => '',
                'objectives' => [],
                'contents' => [],
                'references' => [],
            ],
            'gpt_settings' => [
                'service' => 'openai',
                'model' => 'gpt-3.5-turbo',
                'temperature' => 0.7,
            ],
        ]);
    }
} 