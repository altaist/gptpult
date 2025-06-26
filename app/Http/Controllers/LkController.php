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
                    'status' => $document->status->value, // Техническое значение для цвета
                    'status_label' => $document->status->getLabel(), // Человекочитаемое название
                    'status_color' => $document->status->getColor(), // Цвет из enum
                    'created_at' => $document->created_at->format('Y-m-d'),
                    'document_type' => $document->documentType?->name,
                ];
            });

        // Получаем реальный баланс пользователя из поля balance_rub
        $balance = $user->balance_rub ?? 0;

        return Inertia::render('Lk', [
            'balance' => $balance,
            'documents' => $documents,
            'isDevelopment' => app()->environment(['local', 'testing']),
        ]);
    }
} 