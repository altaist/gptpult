<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\Document;
use App\Enums\DocumentStatus;

class AdminController extends Controller
{
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

        // Последние пользователи
        $recentUsers = User::latest()->take(5)->get(['id', 'name', 'email', 'role_id', 'created_at']);

        // Последние документы
        $recentDocuments = Document::with('user:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'user_id', 'created_at']);

        return Inertia::render('admin/Dashboard', [
            'statistics' => $statistics,
            'recentUsers' => $recentUsers,
            'recentDocuments' => $recentDocuments,
        ]);
    }
} 