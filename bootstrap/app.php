<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\TelegramMiniAppAuth::class,
        ]);
        
        // Заменяем стандартный CSRF middleware на наш с исключениями
        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Регистрируем алиасы middleware
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminRole::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if (!$request->expectsJson()) {
                $status = $e->getStatusCode();
                
                // Определяем доступные страницы ошибок
                $availableErrors = [403, 404, 419, 429, 500, 502, 503];
                
                if (in_array($status, $availableErrors)) {
                    return app(\App\Http\Controllers\ErrorController::class)->showError(
                        $request,
                        $status
                    );
                }
            }
        });
        
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if (!$request->expectsJson()) {
                return app(\App\Http\Controllers\ErrorController::class)->error404($request);
            }
        });
        
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if (!$request->expectsJson()) {
                return app(\App\Http\Controllers\ErrorController::class)->error403($request);
            }
        });
        
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if (!$request->expectsJson()) {
                return app(\App\Http\Controllers\ErrorController::class)->error419($request);
            }
        });
        
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if (!$request->expectsJson()) {
                return app(\App\Http\Controllers\ErrorController::class)->error429($request);
            }
        });
    })->create();
