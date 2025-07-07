<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GptRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_id',
        // 'document_part_id', // ВРЕМЕННО ОТКЛЮЧЕНО: таблица document_parts не существует
        'prompt',
        'response',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Получить документ, к которому относится запрос
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Получить часть документа, к которой относится запрос
     * ВРЕМЕННО ОТКЛЮЧЕНО: модель DocumentPart не существует
     */
    // public function documentPart(): BelongsTo
    // {
    //     return $this->belongsTo(DocumentPart::class);
    // }
} 