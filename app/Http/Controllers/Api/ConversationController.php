<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Establishment;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ConversationController extends Controller
{
    public function __construct(private ChatbotService $chatbot) {}

    public function store(Establishment $establishment): JsonResponse
    {
        abort_unless($this->chatbot->isAvailable($establishment), 404);

        $conversation = $this->chatbot->start($establishment);

        return response()->json([
            'token' => $conversation->token,
            'welcome' => $establishment->chatbot()->welcome_message,
        ], 201);
    }

    public function show(Establishment $establishment, Conversation $conversation): JsonResponse
    {
        abort_unless($this->chatbot->isAvailable($establishment) && $conversation->status === 'active', 404);

        // Igual que el widget: descarta el borrador vencido o avisa si algo del pedido cambió.
        $this->chatbot->resume($establishment, $conversation);

        return response()->json([
            'token' => $conversation->token,
            'messages' => $conversation->messages()->orderBy('id')->get()->map(fn (ConversationMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
                'payload' => $message->payload,
            ]),
        ]);
    }

    public function destroy(Establishment $establishment, Conversation $conversation): Response
    {
        abort_unless($conversation->status === 'active', 404);

        $this->chatbot->close($conversation);

        return response()->noContent();
    }
}
