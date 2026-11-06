<?php

namespace App\Services\Chatbot;

use App\Actions\PlaceOrder;
use App\Models\Conversation;
use App\Models\Establishment;
use Illuminate\Support\Str;

class ChatbotService
{
    private const DRAFT_HOURS = 24;

    public function __construct(
        private StructuredResponder $structured,
        private ResponderFactory $responders,
        private PlaceOrder $placeOrder,
    ) {}

    public function isAvailable(Establishment $establishment): bool
    {
        return $establishment->isPublic()
            && $establishment->chatbot()->enabled
            && $establishment->hasFeature('chatbot');
    }

    public function find(Establishment $establishment, ?string $token): ?Conversation
    {
        if (! $token) {
            return null;
        }

        return $establishment->conversations()
            ->where('token', $token)
            ->where('status', 'active')
            ->first();
    }

    public function start(Establishment $establishment): Conversation
    {
        $token = Str::random(40);

        return $establishment->conversations()->create([
            'token' => $token,
            'customer_identifier' => substr($token, 0, 12),
            'started_at' => now(),
            'last_activity_at' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Cierra la conversación y descarta el pedido que no se confirmó.
     */
    public function close(Conversation $conversation): void
    {
        $conversation->update([
            'status' => 'closed',
            'ended_at' => now(),
            'draft' => null,
        ]);
    }

    /**
     * Si la conversación se interrumpió, recupera el borrador o lo descarta al expirar.
     */
    public function resume(Establishment $establishment, Conversation $conversation): ?ChatReply
    {
        if ($conversation->last_activity_at?->lt(now()->subHours(self::DRAFT_HOURS))) {
            $conversation->update(['draft' => null]);

            return null;
        }

        $reply = $this->orderFor($establishment, $conversation)->resume();

        // Recargar la página varias veces no debe repetir el aviso.
        $alreadyNotified = str_starts_with(
            (string) $conversation->messages()->latest('id')->value('content'),
            OrderConversation::RESUME_PREFIX,
        );

        if ($reply && ! $alreadyNotified) {
            $this->store($conversation, 'assistant', $reply->text, $reply->payload());
        }

        return $reply;
    }

    public function respond(Establishment $establishment, Conversation $conversation, string $message): ChatReply
    {
        abort_unless($conversation->establishment_id === $establishment->id, 404);

        $this->store($conversation, 'user', $message);

        $context = new EstablishmentContext($establishment);
        $normalized = EstablishmentContext::normalize($message);

        $reply = (new OrderConversation($conversation, $context, $this->placeOrder))->handle($message, $normalized);

        if ($reply === null) {
            $reply = $this->responders
                ->make($this->structured)
                ->generate($message, $this->structured->answer($normalized, $context), $context);
        }

        $this->store($conversation, 'assistant', $reply->text, $reply->payload());
        $conversation->update(['last_activity_at' => now()]);

        return $reply;
    }

    private function orderFor(Establishment $establishment, Conversation $conversation): OrderConversation
    {
        return new OrderConversation($conversation, new EstablishmentContext($establishment), $this->placeOrder);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function store(Conversation $conversation, string $role, string $content, ?array $payload = null): void
    {
        $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'payload' => $payload ?: null,
        ]);
    }
}
