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
        Schema::create('telegram_link_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 32)->unique()->comment('Уникальный токен для связки');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ID пользователя');
            $table->string('telegram_id')->nullable()->comment('ID пользователя в Telegram после связки');
            $table->string('telegram_username')->nullable()->comment('Username в Telegram после связки');
            $table->boolean('is_used')->default(false)->comment('Использован ли токен');
            $table->timestamp('expires_at')->comment('Время истечения токена');
            $table->timestamp('used_at')->nullable()->comment('Время использования токена');
            $table->timestamps();
            
            $table->index(['token', 'expires_at']);
            $table->index(['user_id', 'is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_link_tokens');
    }
};
