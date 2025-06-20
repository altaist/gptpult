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
            $table->string('telegram_id')->nullable()->index()->comment('ID телеграм пользователя');
            $table->string('telegram_username')->nullable()->comment('Username в телеграм');
            $table->timestamp('telegram_connected_at')->nullable()->comment('Дата подключения телеграм');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'telegram_connected_at']);
        });
    }
};
