<?php

namespace App\Services\Chatbot;

class ChatReply
{
    /**
     * @param  array<int, array{name: string, price: string, available: bool, description: string|null}>  $cards
     * @param  array{lines: array<int, array{name: string, quantity: int, subtotal: string}>, total: string}|null  $summary
     * @param  array<int, string>  $suggestions
     */
    public function __construct(
        public string $text,
        public array $cards = [],
        public ?array $summary = null,
        public array $suggestions = [],
        public ?string $link = null,
        public bool $isOrderFlow = false,
        public bool $verified = true,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return array_filter([
            'cards' => $this->cards,
            'summary' => $this->summary,
            'suggestions' => $this->suggestions,
            'link' => $this->link,
        ]);
    }
}
