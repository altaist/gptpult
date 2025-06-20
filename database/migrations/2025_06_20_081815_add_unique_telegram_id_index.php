<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Сначала удаляем существующий обычный индекс если есть
            $table->dropIndex(['telegram_id']);
            
            // Добавляем уникальный индекс
            $table->unique('telegram_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем уникальный индекс
            $table->dropUnique(['telegram_id']);
            
            // Восстанавливаем обычный индекс
            $table->index('telegram_id');
        });
    }
};
