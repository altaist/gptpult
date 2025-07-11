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

    /**
     * Создать thread для Assistants API
     *
     * @return array
     */
    public function createThread(): array;

    /**
     * Добавить сообщение в thread
     *
     * @param string $threadId
     * @param string $content
     * @return array
     */
    public function addMessageToThread(string $threadId, string $content): array;

    /**
     * Безопасно добавить сообщение в thread с проверкой активных run
     *
     * @param string $threadId
     * @param string $content
     * @param int $maxRetries
     * @return array
     */
    public function safeAddMessageToThread(string $threadId, string $content, int $maxRetries = 10): array;

    /**
     * Создать run с ассистентом
     *
     * @param string $threadId
     * @param string $assistantId
     * @return array
     */
    public function createRun(string $threadId, string $assistantId): array;

    /**
     * Ждать завершения run
     *
     * @param string $threadId
     * @param string $runId
     * @return array
     */
    public function waitForRunCompletion(string $threadId, string $runId): array;

    /**
     * Получить сообщения из thread
     *
     * @param string $threadId
     * @return array
     */
    public function getThreadMessages(string $threadId): array;

    /**
     * Генерация с веб-поиском
     *
     * @param array $messages
     * @param array $options
     * @return array
     */
    public function generateWithWebSearch(array $messages, array $options = []): array;
} 