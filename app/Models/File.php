<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'document_id',
        'name',
        'display_name',
        'unique_name',
        'path',
        'size',
        'extension',
        'mime_type',
        'storage_disk'
    ];

    /**
     * Получить пользователя, которому принадлежит файл
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить документ, к которому прикреплен файл
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Получить полный путь к файлу
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->storage_disk . '/' . $this->path);
    }

    /**
     * Получить публичный URL файла
     */
    public function getPublicUrl(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Получить размер файла в человекочитаемом формате
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }
} 