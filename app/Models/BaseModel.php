<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'json_data' => 'object',
            'updated_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function scopeCreatedFrom(Builder $query, $dateFrom, $strict = false)
    {
        return $query->when($dateFrom !==null || $strict, fn($query) => $query->where('created_at', '>=', $dateFrom));
    }

    public function scopeCreatedTo(Builder $query, $dateTo, $strict = false)
    {
        return $query->when($dateTo !==null || $strict, fn($query) => $query->where('created_at', '<=', $dateTo));
    }
}
