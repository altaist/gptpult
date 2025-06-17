<?php

namespace App\Services\Orders;

use App\Models\User;
use App\Models\Transition;
use Illuminate\Support\Facades\DB;
use Exception;

class TransitionService
{
    /**
     * Начислить средства на счет пользователя
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @return Transition
     * @throws Exception
     */
    public function creditUser(User $user, float $amount, string $description = 'Пополнение баланса'): Transition
    {
        if ($amount <= 0) {
            throw new Exception('Сумма должна быть положительной');
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            // Получаем текущий баланс
            $amountBefore = $user->balance_rub ?? 0;
            $amountAfter = $amountBefore + $amount;

            // Обновляем баланс пользователя
            $user->update([
                'balance_rub' => $amountAfter
            ]);

            // Создаем запись о транзакции
            $transition = Transition::create([
                'user_id' => $user->id,
                'amount_before' => $amountBefore,
                'amount_after' => $amountAfter,
                'description' => $description
            ]);

            return $transition;
        });
    }

    /**
     * Списать средства со счета пользователя
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @return Transition
     * @throws Exception
     */
    public function debitUser(User $user, float $amount, string $description = 'Списание с баланса'): Transition
    {
        if ($amount <= 0) {
            throw new Exception('Сумма должна быть положительной');
        }

        $currentBalance = $user->balance_rub ?? 0;
        
        if ($currentBalance < $amount) {
            throw new Exception('Недостаточно средств на балансе');
        }

        return DB::transaction(function () use ($user, $amount, $description, $currentBalance) {
            $amountBefore = $currentBalance;
            $amountAfter = $amountBefore - $amount;

            // Обновляем баланс пользователя
            $user->update([
                'balance_rub' => $amountAfter
            ]);

            // Создаем запись о транзакции
            $transition = Transition::create([
                'user_id' => $user->id,
                'amount_before' => $amountBefore,
                'amount_after' => $amountAfter,
                'description' => $description
            ]);

            return $transition;
        });
    }

    /**
     * Перевести средства между пользователями
     *
     * @param User $fromUser
     * @param User $toUser
     * @param float $amount
     * @param string $description
     * @return array
     * @throws Exception
     */
    public function transferBetweenUsers(User $fromUser, User $toUser, float $amount, string $description = 'Перевод средств'): array
    {
        if ($amount <= 0) {
            throw new Exception('Сумма должна быть положительной');
        }

        if ($fromUser->id === $toUser->id) {
            throw new Exception('Нельзя переводить средства самому себе');
        }

        $fromBalance = $fromUser->balance_rub ?? 0;
        
        if ($fromBalance < $amount) {
            throw new Exception('Недостаточно средств на балансе');
        }

        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description, $fromBalance) {
            // Списание с отправителя
            $debitTransition = $this->debitUser(
                $fromUser, 
                $amount, 
                "Перевод пользователю {$toUser->name}: {$description}"
            );

            // Зачисление получателю
            $creditTransition = $this->creditUser(
                $toUser, 
                $amount, 
                "Перевод от пользователя {$fromUser->name}: {$description}"
            );

            return [
                'debit_transition' => $debitTransition,
                'credit_transition' => $creditTransition
            ];
        });
    }

    /**
     * Получить историю транзакций пользователя
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTransitionHistory(User $user, int $limit = 50)
    {
        return $user->transitions()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить баланс пользователя
     *
     * @param User $user
     * @return float
     */
    public function getUserBalance(User $user): float
    {
        return $user->balance_rub ?? 0;
    }
} 