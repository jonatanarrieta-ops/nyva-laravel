<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private ChatbotService $chatbot) {}

    public function store(Request $request, Establishment $establishment, Conversation $conversation): JsonResponse
    {
        abort_unless($this->chatbot->isAvailable($establishment) && $conversation->status === 'active', 404);

        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ], [
            'message.required' => 'Escribe un mensaje.',
            'message.max' => 'El mensaje es demasiado largo.',
        ]);

        $reply = $this->chatbot->respond($establishment, $conversation, $request->string('message')->trim()->toString());

        return response()->json([
            'reply' => ['text' => $reply->text] + $reply->payload(),
        ], 201);
    }
}
