<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TelegramLinkToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'user_id',
        'is_used',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Генерировать новый токен для связки
     */
    public static function generateForTelegramUser(int $telegramUserId, ?string $username = null, ?string $firstName = null, ?string $lastName = null): self
    {
        // Удаляем старые неиспользованные токены для этого пользователя
        self::where('telegram_user_id', $telegramUserId)
            ->where('is_used', false)
            ->delete();

        return self::create([
            'token' => Str::random(32),
            'telegram_user_id' => $telegramUserId,
            'telegram_username' => $username,
            'telegram_first_name' => $firstName,
            'telegram_last_name' => $lastName,
            'expires_at' => now()->addHours(24), // Токен действует 24 часа
        ]);
    }

    /**
     * Найти активный токен
     */
    public static function findActiveToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Использовать токен для связки с пользователем
     */
    public function linkToUser(User $user): bool
    {
        if ($this->is_used || $this->expires_at < now()) {
            return false;
        }

        // Проверяем, не привязан ли уже этот Telegram к другому аккаунту
        $existingUser = User::where('telegram_id', $this->telegram_user_id)->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            return false;
        }

        // Проверяем, не привязан ли уже этот аккаунт к другому Telegram
        if ($user->telegram_id && $user->telegram_id != $this->telegram_user_id) {
            return false;
        }

        // Связываем аккаунт
        $user->update([
            'telegram_id' => $this->telegram_user_id,
            'telegram_username' => $this->telegram_username,
            'telegram_connected_at' => now()
        ]);

        // Помечаем токен как использованный
        $this->update([
            'is_used' => true,
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Проверить, истек ли токен
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Очистить старые токены
     */
    public static function clearExpired(): int
    {
        return self::where('expires_at', '<', now()->subDays(7))->delete();
    }

    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
