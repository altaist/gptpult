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
            'Реферат',
            'Отчет о практике',
            'Эссе'
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