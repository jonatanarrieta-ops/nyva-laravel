<?php

namespace App\Services\Chatbot;

class ResponderFactory
{
    public static function aiConfigured(): bool
    {
        return in_array(config('services.chatbot.driver'), ['anthropic', 'openai'], true)
            && filled(config('services.chatbot.key'))
            && filled(config('services.chatbot.model'));
    }

    public function make(StructuredResponder $structured): ResponseGenerator
    {
        return self::aiConfigured() ? new AiResponder : $structured;
    }
}
