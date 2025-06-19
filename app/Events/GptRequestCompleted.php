<?php

namespace App\Events;

use App\Models\GptRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GptRequestCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public GptRequest $gptRequest
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Проверяем, что document загружен и имеет user_id
        if (!$this->gptRequest->document || !$this->gptRequest->document->user_id) {
            // Если document не загружен, пытаемся загрузить его
            if ($this->gptRequest->document_id) {
                $this->gptRequest->load('document');
            }
            
            // Если все еще нет document или user_id, не делаем broadcast
            if (!$this->gptRequest->document || !$this->gptRequest->document->user_id) {
                return [];
            }
        }

        return [
            new PrivateChannel('user.' . $this->gptRequest->document->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'request_id' => $this->gptRequest->id,
            'document_id' => $this->gptRequest->document_id,
            'status' => 'completed',
            'response' => $this->gptRequest->response,
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * @return bool
     */
    public function broadcastWhen(): bool
    {
        // Не делаем broadcast для фиктивных GptRequest без ID
        return $this->gptRequest->id !== null && 
               $this->gptRequest->document_id !== null &&
               $this->gptRequest->document !== null &&
               $this->gptRequest->document->user_id !== null;
    }
} 