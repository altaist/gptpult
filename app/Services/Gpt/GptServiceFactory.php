<?php

namespace App\Services\Gpt;

use InvalidArgumentException;

class GptServiceFactory
{
    protected array $services = [
        'openai' => OpenAiService::class,
        'anthropic' => AnthropicService::class,
    ];

    /**
     * Создать экземпляр GPT сервиса
     *
     * @param string $service
     * @return GptServiceInterface
     */
    public function make(string $service): GptServiceInterface
    {
        if (!isset($this->services[$service])) {
            throw new InvalidArgumentException("GPT service '{$service}' not found");
        }

        $class = $this->services[$service];
        return new $class();
    }

    /**
     * Получить список доступных сервисов
     *
     * @return array
     */
    public function getAvailableServices(): array
    {
        return array_keys($this->services);
    }
} 