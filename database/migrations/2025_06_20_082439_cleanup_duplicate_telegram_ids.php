<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Находим все дублированные telegram_id
        $duplicates = DB::table('users')
            ->select('telegram_id')
            ->whereNotNull('telegram_id')
            ->groupBy('telegram_id')
            ->havingRaw('count(*) > 1')
            ->pluck('telegram_id');

        Log::info("Found {$duplicates->count()} duplicate telegram_id values");

        foreach ($duplicates as $telegramId) {
            // Получаем всех пользователей с этим telegram_id
            $users = DB::table('users')
                ->where('telegram_id', $telegramId)
                ->orderBy('telegram_connected_at', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Log::info("Processing telegram_id {$telegramId} with {$users->count()} users");

            // Оставляем telegram_id только у первого пользователя (последний подключенный)
            $keepUser = $users->first();
            
            foreach ($users->skip(1) as $user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'telegram_id' => null,
                        'telegram_username' => null,
                        'telegram_connected_at' => null
                    ]);
                    
                Log::info("Cleared telegram data for user {$user->id} (kept for user {$keepUser->id})");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Не можем откатить очистку дублей, так как не знаем исходного состояния
        Log::warning('Cannot rollback cleanup_duplicate_telegram_ids migration');
    }
};
