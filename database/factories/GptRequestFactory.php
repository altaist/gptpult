<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\GptRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GptRequest>
 */
class GptRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GptRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'prompt' => $this->faker->paragraph(),
            'response' => $this->faker->paragraphs(2, true),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'error_message' => $this->faker->optional(0.2)->sentence(),
            'metadata' => [
                'model' => $this->faker->randomElement(['gpt-3.5-turbo', 'gpt-4']),
                'tokens_used' => $this->faker->numberBetween(100, 2000),
                'temperature' => $this->faker->randomFloat(1, 0.1, 1.0),
            ],
        ];
    }

    /**
     * Указать, что запрос выполнен успешно
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'error_message' => null,
        ]);
    }

    /**
     * Указать, что запрос завершился с ошибкой
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
        ]);
    }
} 