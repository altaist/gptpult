<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    protected string $secretKey;
    protected string $siteKey;
    protected bool $enabled;

    public function __construct()
    {
        $this->secretKey = config('services.recaptcha.secret_key');
        $this->siteKey = config('services.recaptcha.site_key');
        $this->enabled = config('services.recaptcha.enabled', true);
    }

    /**
     * Проверить токен reCAPTCHA
     */
    public function verify(string $token, string $remoteIp = null): array
    {
        // Если reCAPTCHA отключена, возвращаем успех
        if (!$this->enabled) {
            return [
                'success' => true,
                'score' => 1.0,
                'action' => 'disabled',
                'hostname' => null,
                'challenge_ts' => now()->toISOString(),
                'disabled' => true
            ];
        }

        // Проверяем наличие ключей
        if (empty($this->secretKey)) {
            Log::warning('reCAPTCHA secret key not configured');
            return [
                'success' => false,
                'error-codes' => ['missing-secret-key'],
                'message' => 'reCAPTCHA not configured'
            ];
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $remoteIp
            ]);

            if (!$response->successful()) {
                Log::error('reCAPTCHA verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'error-codes' => ['http-error'],
                    'message' => 'reCAPTCHA service unavailable'
                ];
            }

            $result = $response->json();

            // Логируем результат проверки
            Log::info('reCAPTCHA verification result', [
                'success' => $result['success'] ?? false,
                'score' => $result['score'] ?? null,
                'action' => $result['action'] ?? null,
                'hostname' => $result['hostname'] ?? null,
                'error_codes' => $result['error-codes'] ?? []
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification exception', [
                'error' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...'
            ]);

            return [
                'success' => false,
                'error-codes' => ['exception'],
                'message' => 'reCAPTCHA verification failed'
            ];
        }
    }

    /**
     * Проверить токен reCAPTCHA v3 с минимальным скором
     */
    public function verifyV3(string $token, string $action, float $minScore = 0.5, string $remoteIp = null): array
    {
        $result = $this->verify($token, $remoteIp);

        if (!$result['success']) {
            return $result;
        }

        // Проверяем действие (action)
        if (isset($result['action']) && $result['action'] !== $action) {
            Log::warning('reCAPTCHA action mismatch', [
                'expected' => $action,
                'actual' => $result['action']
            ]);
            
            return [
                'success' => false,
                'error-codes' => ['action-mismatch'],
                'message' => 'Invalid action'
            ];
        }

        // Проверяем скор (только для v3)
        if (isset($result['score'])) {
            if ($result['score'] < $minScore) {
                Log::warning('reCAPTCHA score too low', [
                    'score' => $result['score'],
                    'min_score' => $minScore,
                    'action' => $action
                ]);
                
                return [
                    'success' => false,
                    'error-codes' => ['score-too-low'],
                    'message' => 'Security check failed',
                    'score' => $result['score']
                ];
            }
        }

        return $result;
    }

    /**
     * Получить site key для фронтенда
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * Проверить, включена ли reCAPTCHA
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->secretKey) && !empty($this->siteKey);
    }
} 