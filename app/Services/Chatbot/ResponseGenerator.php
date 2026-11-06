<?php

namespace App\Services\Chatbot;

interface ResponseGenerator
{
    /**
     * Redacta la respuesta informativa. Nunca participa en el flujo de pedidos.
     *
     * @param  array<int, array{role: string, content: string}>  $history  Mensajes anteriores, del más antiguo al más reciente.
     */
    public function generate(string $message, ChatReply $structured, EstablishmentContext $context, array $history = []): ChatReply;
}
