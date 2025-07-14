<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Определить, может ли пользователь просматривать документ
     */
    public function view(User $user, Document $document): bool
    {
        // Администраторы могут просматривать все документы
        if ($user->isAdmin()) {
            return true;
        }

        // Обычные пользователи могут просматривать только свои документы
        return $user->id === $document->user_id;
    }

    /**
     * Определить, может ли пользователь обновлять документ
     */
    public function update(User $user, Document $document): bool
    {
        // Администраторы могут обновлять все документы
        if ($user->isAdmin()) {
            return true;
        }

        // Обычные пользователи могут обновлять только свои документы
        return $user->id === $document->user_id;
    }

    /**
     * Определить, может ли пользователь удалять документ
     */
    public function delete(User $user, Document $document): bool
    {
        // Администраторы могут удалять все документы
        if ($user->isAdmin()) {
            return true;
        }

        // Обычные пользователи могут удалять только свои документы
        return $user->id === $document->user_id;
    }
} 