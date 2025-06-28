<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Orders\TransitionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LkController extends Controller
{
    protected TransitionService $transitionService;

    public function __construct(TransitionService $transitionService)
    {
        $this->transitionService = $transitionService;
    }

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

    /**
     * API: Получить историю транзакций пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransitionHistory(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 20);

        $transitions = $this->transitionService->getUserTransitionHistory($user, $limit);

        return response()->json([
            'success' => true,
            'transitions' => $transitions->map(function ($transition) {
                return [
                    'id' => $transition->id,
                    'amount_before' => $transition->amount_before,
                    'amount_after' => $transition->amount_after,
                    'difference' => $transition->difference,
                    'description' => $transition->description,
                    'is_credit' => $transition->isCredit(),
                    'is_debit' => $transition->isDebit(),
                    'created_at' => $transition->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $transition->created_at->diffForHumans(),
                ];
            }),
            'current_balance' => $this->transitionService->getUserBalance($user)
        ]);
    }
} 