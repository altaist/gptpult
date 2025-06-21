<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestDatabaseConnection implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('document_creates');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('TestDatabaseConnection: Начало теста');
            
            // Проверяем переменные окружения
            $dbConnection = env('DB_CONNECTION');
            $dbDatabase = env('DB_DATABASE');
            $configDatabase = config('database.connections.sqlite.database');
            
            Log::info('TestDatabaseConnection: Environment info', [
                'DB_CONNECTION' => $dbConnection,
                'DB_DATABASE' => $dbDatabase,
                'config_database' => $configDatabase,
                'base_path' => base_path(),
                'database_path' => database_path(),
                'cwd' => getcwd(),
            ]);
            
            // Проверяем доступность базы
            $documentCount = \App\Models\Document::count();
            
            Log::info('TestDatabaseConnection: Database accessible', [
                'document_count' => $documentCount
            ]);
            
            // Проверяем сессии
            $sessionCount = \Illuminate\Support\Facades\DB::table('sessions')->count();
            
            Log::info('TestDatabaseConnection: Sessions accessible', [
                'session_count' => $sessionCount
            ]);
            
        } catch (\Exception $e) {
            Log::error('TestDatabaseConnection: Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
