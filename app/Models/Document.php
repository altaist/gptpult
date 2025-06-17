<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory, SoftDeletes, AuthorizesRequests;

    protected $fillable = [
        'user_id',
        'document_type_id',
        'title',
        'structure',
        'content',
        'pages_num',
        'gpt_settings',
        'status'
    ];

    protected $casts = [
        'structure' => 'array',
        'gpt_settings' => 'array',
        'status' => DocumentStatus::class
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Получить все файлы документа
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }

    /**
     * Получить файл определенного типа
     */
    public function getFileByMimeType(string $mimeType): ?File
    {
        return $this->files()->where('mime_type', $mimeType)->first();
    }

    /**
     * Проверить, есть ли файл определенного типа
     */
    public function hasFileWithMimeType(string $mimeType): bool
    {
        return $this->files()->where('mime_type', $mimeType)->exists();
    }

    /**
     * Отношение к заказам
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

} 