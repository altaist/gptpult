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
            // Поля telegram_id, telegram_username, telegram_connected_at уже существуют
            // Добавляем только недостающие поля
            $table->string('telegram_link_token')->nullable()->after('telegram_username');
            // Переименовываем telegram_connected_at в telegram_linked_at
            $table->renameColumn('telegram_connected_at', 'telegram_linked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_link_token');
            $table->renameColumn('telegram_linked_at', 'telegram_connected_at');
        });
    }
}; 