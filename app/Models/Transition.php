<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transition extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'amount_before',
        'amount_after',
        'description'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'amount_before' => 'decimal:2',
        'amount_after' => 'decimal:2'
    ];

    /**
     * Отношение к пользователю
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить разность между балансами
     */
    public function getDifferenceAttribute(): float
    {
        return $this->amount_after - $this->amount_before;
    }

    /**
     * Проверить, является ли операция пополнением
     */
    public function isCredit(): bool
    {
        return $this->amount_after > $this->amount_before;
    }

    /**
     * Проверить, является ли операция списанием
     */
    public function isDebit(): bool
    {
        return $this->amount_after < $this->amount_before;
    }
} 