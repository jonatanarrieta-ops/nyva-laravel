<?php

namespace App\Services\Chatbot;

use App\Actions\PlaceOrder;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Establishment;
use Illuminate\Support\Str;

class ChatbotService
{
    private const DRAFT_HOURS = 24;

    /** Marca en el payload de los mensajes que pertenecen al flujo de pedido. */
    public const ORDER_FLAG = 'order';

    /** Cantidad de mensajes anteriores que recibe la IA. */
    private const HISTORY_SIZE = 8;

    /** Mensajes recientes que se revisan para armar el historial. */
    private const HISTORY_SCAN = 30;

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
            $this->store($conversation, 'assistant', $reply->text, $this->payloadFor($reply));
        }

        return $reply;
    }

    public function respond(Establishment $establishment, Conversation $conversation, string $message): ChatReply
    {
        abort_unless($conversation->establishment_id === $establishment->id, 404);

        $history = $this->history($conversation);
        $userMessage = $this->store($conversation, 'user', $message);

        $context = new EstablishmentContext($establishment);
        $normalized = EstablishmentContext::normalize($message);

        $reply = (new OrderConversation($conversation, $context, $this->placeOrder))->handle($message, $normalized);

        if ($reply === null) {
            $reply = $this->responders
                ->make($this->structured)
                ->generate($message, $this->structured->answer($normalized, $context), $context, $history);
        }

        if ($reply->isOrderFlow) {
            $userMessage->update(['payload' => [self::ORDER_FLAG => true]]);
        }

        $this->store($conversation, 'assistant', $reply->text, $this->payloadFor($reply));
        $conversation->update(['last_activity_at' => now()]);

        return $reply;
    }

    /**
     * Últimos mensajes informativos para que la IA entienda el contexto.
     * Los del pedido se excluyen porque contienen nombre, teléfono y dirección del cliente.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function history(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest('id')
            ->limit(self::HISTORY_SCAN)
            ->get()
            ->reject(fn (ConversationMessage $message) => (bool) ($message->payload[self::ORDER_FLAG] ?? false))
            ->take(self::HISTORY_SIZE)
            ->reverse()
            ->map(fn (ConversationMessage $message) => ['role' => $message->role, 'content' => $message->content])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(ChatReply $reply): array
    {
        return $reply->isOrderFlow ? [...$reply->payload(), self::ORDER_FLAG => true] : $reply->payload();
    }

    private function orderFor(Establishment $establishment, Conversation $conversation): OrderConversation
    {
        return new OrderConversation($conversation, new EstablishmentContext($establishment), $this->placeOrder);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function store(Conversation $conversation, string $role, string $content, ?array $payload = null): ConversationMessage
    {
        return $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'payload' => $payload ?: null,
        ]);
    }
}
