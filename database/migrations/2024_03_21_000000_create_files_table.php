<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name'); // Оригинальное имя файла
            $table->string('display_name'); // Пользовательское имя
            $table->string('unique_name')->unique(); // Уникальное имя файла
            $table->string('path'); // Путь к файлу
            $table->bigInteger('size'); // Размер в байтах
            $table->string('extension'); // Расширение файла
            $table->string('mime_type'); // MIME тип
            $table->string('storage_disk')->default('public'); // Диск хранения
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
}; 