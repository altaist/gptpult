<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class ErrorController extends Controller
{
    /**
     * Отобразить страницу ошибки 403 (Доступ запрещен)
     */
    public function error403(Request $request): HttpResponse
    {
        return $this->renderErrorPage(403);
    }

    /**
     * Отобразить страницу ошибки 404 (Страница не найдена)
     */
    public function error404(Request $request): HttpResponse
    {
        return $this->renderErrorPage(404);
    }

    /**
     * Отобразить страницу ошибки 419 (Сессия истекла)
     */
    public function error419(Request $request): HttpResponse
    {
        return $this->renderErrorPage(419);
    }

    /**
     * Отобразить страницу ошибки 429 (Слишком много запросов)
     */
    public function error429(Request $request): HttpResponse
    {
        return $this->renderErrorPage(429);
    }

    /**
     * Отобразить страницу ошибки 500 (Внутренняя ошибка сервера)
     */
    public function error500(Request $request): HttpResponse
    {
        return $this->renderErrorPage(500);
    }

    /**
     * Отобразить страницу ошибки 502 (Сервер недоступен)
     */
    public function error502(Request $request): HttpResponse
    {
        return $this->renderErrorPage(502);
    }

    /**
     * Отобразить страницу ошибки 503 (Сервис недоступен)
     */
    public function error503(Request $request): HttpResponse
    {
        return $this->renderErrorPage(503);
    }

    /**
     * Универсальный метод для отображения любой ошибки
     */
    public function showError(Request $request, int $status): HttpResponse
    {
        return $this->renderErrorPage($status);
    }

    /**
     * Приватный метод для рендеринга страницы ошибки
     */
    private function renderErrorPage(int $status): HttpResponse
    {
        return Inertia::render('errors/ErrorPage', [
            'status' => $status
        ])->toResponse(request())->setStatusCode($status);
    }
} 