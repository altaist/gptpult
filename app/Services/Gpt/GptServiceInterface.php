<?php

namespace App\Services\Gpt;

interface GptServiceInterface
{
    /**
     * Отправить запрос к GPT сервису
     *
     * @param string $prompt
     * @param array $options
     * @return array
     */
    public function sendRequest(string $prompt, array $options = []): array;

    /**
     * Получить название сервиса
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Получить доступные модели
     *
     * @return array
     */
    public function getAvailableModels(): array;
} 