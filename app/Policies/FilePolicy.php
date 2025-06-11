<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilePolicy
{
    use HandlesAuthorization;

    /**
     * Определяет, может ли пользователь просматривать файл
     */
    public function view(User $user, File $file): bool
    {
        // Пользователь может просматривать свои файлы
        if ($user->id === $file->user_id) {
            return true;
        }

        // Если файл привязан к документу, проверяем права на документ
        if ($file->document_id) {
            return $user->can('view', $file->document);
        }

        return false;
    }

    /**
     * Определяет, может ли пользователь скачивать файл
     */
    public function download(User $user, File $file): bool
    {
        return $this->view($user, $file);
    }

    /**
     * Определяет, может ли пользователь обновлять файл
     */
    public function update(User $user, File $file): bool
    {
        return $user->id === $file->user_id;
    }

    /**
     * Определяет, может ли пользователь удалять файл
     */
    public function delete(User $user, File $file): bool
    {
        return $user->id === $file->user_id;
    }
} 