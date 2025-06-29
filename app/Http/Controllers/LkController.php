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
            'auth' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'privacy_consent' => $user->privacy_consent ?? false,
                    'privacy_consent_at' => $user->privacy_consent_at?->format('Y-m-d H:i:s'),
                ]
            ],
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

    /**
     * API: Тестовое уменьшение баланса (только для development)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDecrementBalance(Request $request)
    {
        // Проверяем, что мы в development режиме
        if (!app()->environment(['local', 'testing'])) {
            return response()->json([
                'success' => false,
                'error' => 'Функция доступна только в режиме разработки'
            ], 403);
        }

        $user = $request->user();
        
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000'
        ]);

        $amount = $request->input('amount');

        try {
            // Проверяем достаточность средств
            $currentBalance = $this->transitionService->getUserBalance($user);
            
            if ($currentBalance < $amount) {
                return response()->json([
                    'success' => false,
                    'error' => 'Недостаточно средств на балансе'
                ], 400);
            }

            // Списываем средства
            $transition = $this->transitionService->debitUser(
                $user, 
                $amount, 
                'Тестовое списание (режим разработки)'
            );

            return response()->json([
                'success' => true,
                'message' => 'Баланс успешно уменьшен',
                'new_balance' => $this->transitionService->getUserBalance($user),
                'transition' => [
                    'id' => $transition->id,
                    'amount_before' => $transition->amount_before,
                    'amount_after' => $transition->amount_after,
                    'difference' => $transition->difference,
                    'description' => $transition->description,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при уменьшении баланса: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Обновить email и согласие на обработку персональных данных
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserContact(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'email' => 'required|email|max:255',
            'privacy_consent' => 'required|boolean'
        ]);

        $email = $request->input('email');
        $privacyConsent = $request->input('privacy_consent');

        try {
            // Проверяем согласие на обработку данных
            if (!$privacyConsent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Необходимо дать согласие на обработку персональных данных'
                ], 422);
            }

            // Проверяем уникальность email (исключая текущего пользователя)
            $existingUser = \App\Models\User::where('email', $email)
                ->where('id', '!=', $user->id)
                ->first();
                
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Пользователь с таким email уже существует'
                ], 422);
            }

            // Обновляем данные пользователя
            $updateData = [
                'email' => $email,
                'privacy_consent' => $privacyConsent
            ];

            // Если согласие было дано впервые, записываем дату
            if ($privacyConsent && !$user->privacy_consent) {
                $updateData['privacy_consent_at'] = now();
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Контактные данные успешно обновлены'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка при обновлении данных: ' . $e->getMessage()
            ], 500);
        }
    }
} 