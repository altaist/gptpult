<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\RecaptchaService;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    protected RecaptchaService $recaptchaService;

    public function __construct(RecaptchaService $recaptchaService)
    {
        $this->recaptchaService = $recaptchaService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'submit', float $minScore = 0.5): Response
    {
        // Если reCAPTCHA отключена, пропускаем проверку
        if (!$this->recaptchaService->isEnabled()) {
            return $next($request);
        }

        $token = $request->input('recaptcha_token');

        if (!$token) {
            return response()->json([
                'message' => 'Требуется подтверждение безопасности',
                'recaptcha_required' => true
            ], 422);
        }

        $result = $this->recaptchaService->verifyV3($token, $action, $minScore, $request->ip());

        if (!$result['success']) {
            return response()->json([
                'message' => 'Проверка безопасности не пройдена. Попробуйте ещё раз.',
                'recaptcha_error' => $result['message'] ?? 'reCAPTCHA verification failed'
            ], 422);
        }

        // Добавляем результат проверки в request для дальнейшего использования
        $request->merge(['recaptcha_result' => $result]);

        return $next($request);
    }
} 