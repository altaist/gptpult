<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\Document;
use App\Enums\DocumentStatus;
use Illuminate\Support\Facades\DB;
use App\Services\Queue\WorkerManagementService;

class AdminController extends Controller
{
    protected WorkerManagementService $workerService;

    public function __construct(WorkerManagementService $workerService)
    {
        $this->workerService = $workerService;
    }

    /**
     * Главная страница админки
     */
    public function index()
    {
        // Статистика
        $statistics = [
            'users_total' => User::count(),
            'documents_total' => Document::count(),
            'documents_completed' => Document::where('status', DocumentStatus::FULL_GENERATED)->count(),
            'documents_processing' => Document::whereIn('status', [
                DocumentStatus::PRE_GENERATING,
                DocumentStatus::FULL_GENERATING
            ])->count(),
        ];

        // Статистика очередей
        $queueStats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'document_queue_pending' => DB::table('jobs')->where('queue', 'document_creates')->count(),
            'document_queue_failed' => DB::table('failed_jobs')->where('queue', 'document_creates')->count(),
        ];

        // Статистика воркеров
        $workerStats = $this->workerService->getWorkerStats();

        // Последние пользователи
        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'role_id', 'created_at']);

        // Последние документы
        $recentDocuments = Document::with('user:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'user_id', 'created_at']);

        return Inertia::render('admin/Dashboard', [
            'statistics' => $statistics,
            'queueStats' => $queueStats,
            'workerStats' => $workerStats,
            'recentUsers' => $recentUsers,
            'recentDocuments' => $recentDocuments,
        ]);
    }
} 