<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'telegram_id',
        'telegram_username',
        'is_used',
        'expires_at',
        'used_at'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Связь с пользователем
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверить, не истек ли токен
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Проверить, можно ли использовать токен
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Отметить токен как использованный
     */
    public function markAsUsed(string $telegramId, ?string $telegramUsername = null): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'telegram_id' => $telegramId,
            'telegram_username' => $telegramUsername,
        ]);
    }

    /**
     * Сгенерировать новый токен для пользователя
     */
    public static function generateForUser(User $user, int $expirationHours = 24): self
    {
        // Очищаем старые неиспользованные токены пользователя
        self::where('user_id', $user->id)
            ->where('is_used', false)
            ->delete();

        return self::create([
            'token' => self::generateUniqueToken(),
            'user_id' => $user->id,
            'expires_at' => now()->addHours($expirationHours),
        ]);
    }

    /**
     * Сгенерировать уникальный токен
     */
    private static function generateUniqueToken(): string
    {
        do {
            $token = \Illuminate\Support\Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Найти действительный токен
     */
    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
