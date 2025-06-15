<?php

namespace App\Services\Files;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilesService
{
    /**
     * Базовые директории для разных типов файлов
     */
    private array $baseDirectories = [
        'documents' => 'documents',
        'images' => 'images',
        'default' => 'files'
    ];

    /**
     * Сохранить файл и создать запись в базе данных
     *
     * @param UploadedFile|string $file Путь к файлу или UploadedFile
     * @param User $user Пользователь
     * @param string|null $displayName Пользовательское имя файла
     * @param string|null $documentId ID документа
     * @return File
     */
    public function saveFile(
        UploadedFile|string $file,
        User $user,
        ?string $displayName = null,
        ?string $documentId = null
    ): File {
        // Если передан путь к файлу, создаем UploadedFile
        if (is_string($file)) {
            $file = new UploadedFile(
                $file,
                basename($file),
                mime_content_type($file),
                null,
                true
            );
        }

        // Получаем информацию о файле
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Генерируем уникальное имя файла
        $uniqueName = $this->generateUniqueFileName($originalName);

        // Сохраняем файл в корень
        Storage::disk('public')->putFileAs(
            '',
            $file,
            $uniqueName
        );

        // Создаем запись в базе данных
        return File::create([
            'user_id' => $user->id,
            'document_id' => $documentId,
            'name' => $originalName,
            'display_name' => $displayName ?? $originalName,
            'unique_name' => $uniqueName,
            'path' => $uniqueName,
            'size' => $size,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'storage_disk' => 'public'
        ]);
    }

    /**
     * Создать запись о файле по его пути
     *
     * @param string $filePath Полный путь к файлу
     * @param User $user Пользователь
     * @param string|null $displayName Пользовательское имя файла
     * @param string|null $documentId ID документа
     * @return File
     */
    public function createFileFromPath(
        string $filePath,
        User $user,
        ?string $displayName = null,
        ?string $documentId = null
    ): File {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Файл не найден: {$filePath}");
        }

        $originalName = basename($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = mime_content_type($filePath);
        $size = filesize($filePath);

        // Получаем относительный путь от storage/app/public
        $relativePath = str_replace(storage_path('app/public/'), '', $filePath);

        // Создаем запись в базе данных
        return File::create([
            'user_id' => $user->id,
            'document_id' => $documentId,
            'name' => $originalName,
            'display_name' => $displayName ?? $originalName,
            'unique_name' => basename($relativePath),
            'path' => $relativePath,
            'size' => $size,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'storage_disk' => 'public'
        ]);
    }

    /**
     * Генерирует уникальное имя файла
     */
    private function generateUniqueFileName(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
        
        return Str::slug($nameWithoutExtension) . '_' . Str::random(10) . '.' . $extension;
    }

    /**
     * Удалить файл и его запись из базы данных
     */
    public function deleteFile(File $file): bool
    {
        // Удаляем физический файл
        Storage::disk($file->storage_disk)->delete($file->path);
        
        // Удаляем запись из базы данных
        return $file->delete();
    }
} 