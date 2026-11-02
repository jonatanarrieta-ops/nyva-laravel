<?php

namespace App\Services\Chatbot;

interface ResponseGenerator
{
    /**
     * Redacta la respuesta informativa. Nunca participa en el flujo de pedidos.
     */
    public function generate(string $message, ChatReply $structured, EstablishmentContext $context): ChatReply;
}
