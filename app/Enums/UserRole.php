<?php

namespace App\Enums;

enum UserRole: int
{
    case USER = 0;
    case ADMIN = 1;

    public function label(): string
    {
        return match($this) {
            self::USER => 'Обычный пользователь',
            self::ADMIN => 'Администратор',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isUser(): bool
    {
        return $this === self::USER;
    }
} 