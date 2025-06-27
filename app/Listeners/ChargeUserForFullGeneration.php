<?php

namespace App\Listeners;

use App\Events\FullGenerationCompleted;
use App\Services\Orders\TransitionService;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\Log;

class ChargeUserForFullGeneration
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected TransitionService $transitionService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(FullGenerationCompleted $event): void
    {
        try {
            $document = $event->document;
            $user = $document->user;
            $amount = OrderService::DEFAULT_PRICE;

            // Списываем средства
            

            Log::info('Списаны средства за полную генерацию', [
                'document_id' => $document->id,
                'user_id' => $user->id,
                'amount' => $amount
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при списании средств за полную генерацию', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 