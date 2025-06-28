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
            // Добавляем недостающие telegram поля
            $table->string('telegram_id')->nullable()->after('statistics');
            $table->string('telegram_username')->nullable()->after('telegram_id');
            $table->string('telegram_link_token')->nullable()->after('telegram_username');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_link_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'telegram_link_token', 'telegram_linked_at']);
        });
    }
}; 