<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Responde solo con información registrada por el establecimiento.
 */
class StructuredResponder implements ResponseGenerator
{
    public function __construct(private IntentResolver $intents) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function generate(string $message, ChatReply $structured, EstablishmentContext $context, array $history = []): ChatReply
    {
        return $structured;
    }

    public function answer(string $normalized, EstablishmentContext $context): ChatReply
    {
        $establishment = $context->establishment;

        return match ($this->intents->resolve($normalized, $context)) {
            IntentResolver::FAQ => new ChatReply((string) $this->intents->bestFaq($normalized, $context)),
            IntentResolver::GREETING => new ChatReply(
                "¡Hola! Soy el asistente de {$establishment->name}. Puedo contarte sobre productos, precios y horarios, o ayudarte con tu pedido.",
                suggestions: $this->defaultSuggestions(),
            ),
            IntentResolver::HOURS => $this->hours($context),
            IntentResolver::LOCATION => new ChatReply(trim(
                "Estamos en {$establishment->address}. Teléfono: {$establishment->phone}."
                .($establishment->email ? " Correo: {$establishment->email}." : '')
            )),
            IntentResolver::CATEGORIES => $this->categories($context),
            IntentResolver::PRODUCT => $this->products($context->matchProducts($normalized)),
            IntentResolver::MENU => $this->menu($context),
            default => new ChatReply(
                'No tengo esa información. Puedo ayudarte con productos, precios, horarios y pedidos.'
                ." También puedes llamar al {$establishment->phone}.",
                suggestions: $this->defaultSuggestions(),
                verified: false,
            ),
        };
    }

    private function hours(EstablishmentContext $context): ChatReply
    {
        $lines = $context->scheduleLines();

        if ($lines->isEmpty()) {
            return new ChatReply('Este establecimiento todavía no ha publicado sus horarios.');
        }

        $state = $context->establishment->isWithinSchedule() ? 'Ahora estamos en horario de atención.' : 'Ahora estamos fuera del horario de atención.';

        return new ChatReply($lines->implode("\n")."\n{$state}");
    }

    private function categories(EstablishmentContext $context): ChatReply
    {
        $categories = $context->categories();

        if ($categories->isEmpty()) {
            return new ChatReply('Todavía no hay categorías publicadas.');
        }

        return new ChatReply(
            'Estas son nuestras categorías: '.$categories->pluck('name')->implode(', ').'.',
            suggestions: ['Ver menú'],
        );
    }

    private function menu(EstablishmentContext $context): ChatReply
    {
        $products = $context->products();

        if ($products->isEmpty()) {
            return new ChatReply('Todavía no hay productos publicados.');
        }

        return new ChatReply(
            'Esto es lo que tenemos:',
            cards: $this->cards($products->take(12)),
            suggestions: $products->count() > 12 ? ['Ver categorías'] : [],
        );
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function products(Collection $products): ChatReply
    {
        $text = $products->count() === 1
            ? "{$products->first()->name}: ".EstablishmentContext::money($products->first()->price).'. '
                .($products->first()->isOrderable() ? 'Está disponible.' : 'No está disponible en este momento.')
            : 'Encontré estas opciones:';

        return new ChatReply($text, cards: $this->cards($products));
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{name: string, price: string, available: bool, description: string|null}>
     */
    private function cards(Collection $products): array
    {
        return $products->map(fn (Product $product) => [
            'name' => $product->name,
            'price' => EstablishmentContext::money($product->price),
            'available' => $product->isOrderable(),
            'description' => $product->description,
        ])->values()->all();
    }

    /**
     * @return array<int, string>
     */
    private function defaultSuggestions(): array
    {
        return ['Ver menú', 'Horarios', 'Quiero hacer un pedido'];
    }
}
