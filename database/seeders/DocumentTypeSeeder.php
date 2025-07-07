<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Отчет о практике',
            'Курсовая работа',
            'Доклад',
            'Эссе',
            'Реферат',
            'Научная статья'
        ];

        foreach ($types as $type) {
            DocumentType::create([
                'name' => $type,
                'slug' => Str::slug($type),
                'description' => "Тип документа: {$type}"
            ]);
        }
    }
} 