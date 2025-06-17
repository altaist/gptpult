<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LkController extends Controller
{
    /**
     * Отображение главной страницы личного кабинета
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Получаем документы пользователя
        $documents = Document::where('user_id', $user->id)
            ->with('documentType')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'status' => $document->status->value,
                    'created_at' => $document->created_at->format('Y-m-d'),
                    'document_type' => $document->documentType?->name,
                ];
            });

        // Пример баланса (в реальном приложении получать из модели пользователя или отдельной таблицы)
        $balance = $user->balance ?? 15000;

        return Inertia::render('Lk', [
            'balance' => $balance,
            'documents' => $documents,
        ]);
    }
} 