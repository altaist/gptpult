<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Document extends Model
{
    use HasFactory, SoftDeletes, AuthorizesRequests;

    protected $fillable = [
        'user_id',
        'document_type_id',
        'title',
        'structure',
        'status'
    ];

    protected $casts = [
        'structure' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
} 