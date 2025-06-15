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
            'status' => $this->faker->randomElement(['draft', 'in_review', 'approved', 'rejected']),
        ];
    }
} 