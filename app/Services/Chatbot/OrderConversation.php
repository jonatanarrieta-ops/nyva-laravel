<?php

namespace App\Services\Chatbot;

use App\Actions\PlaceOrder;
use App\Models\Conversation;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pedido por chat como máquina de estados guardada en la conversación.
 * El pedido solo se registra con una confirmación explícita en la etapa de revisión.
 */
class OrderConversation
{
    private const BROWSING = 'browsing';

    private const NAME = 'customer_name';

    private const PHONE = 'customer_phone';

    private const ADDRESS = 'delivery_address';

    private const REVIEW = 'review';

    private const NUMBERS = ['un' => 1, 'una' => 1, 'uno' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5, 'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10];

    public function __construct(
        private Conversation $conversation,
        private EstablishmentContext $context,
        private PlaceOrder $placeOrder,
    ) {}

    /**
     * Devuelve la respuesta si el mensaje pertenece al pedido, o null si es informativo.
     */
    public function handle(string $raw, string $message): ?ChatReply
    {
        $draft = $this->draft();
        $stage = $draft['stage'];

        if (preg_match('/^(cancelar|cancela|cancelo|anular)\b/', $message)) {
            return $this->hasDraft() ? $this->cancel() : null;
        }

        if ($stage === self::REVIEW) {
            return $this->review($message);
        }

        if (preg_match('/\b(ver pedido|mi pedido|resumen)\b/', $message) && $this->hasDraft()) {
            return $this->summaryReply('Este es tu pedido:');
        }

        if (preg_match('/\b(quita|quitar|quitame|elimina|eliminar|saca|sacar|borra|borrar)\b/', $message)) {
            return $this->hasDraft() ? $this->remove($message) : null;
        }

        if (preg_match('/\b(cambia|cambiar|modifica|modificar|pon|deja|actualiza)\b/', $message) && $this->hasDraft()) {
            return $this->setQuantity($message);
        }

        if (in_array($stage, [self::NAME, self::PHONE, self::ADDRESS], true)) {
            return $this->collect($stage, $raw);
        }

        if (preg_match('/\b(listo|eso es todo|finalizar|terminar|continuar)\b/', $message) && $this->hasDraft()) {
            return $this->askCustomerData();
        }

        if (preg_match('/\b(quiero|quisiera|deseo|dame|agrega|agregar|agregame|anade|anadir|pedir|ordenar|comprar|pedido)\b/', $message)) {
            return $this->add($message);
        }

        return null;
    }

    public function hasDraft(): bool
    {
        return $this->draft()['items'] !== [];
    }

    /**
     * Retoma un pedido interrumpido y avisa si algo cambió.
     */
    public function resume(): ?ChatReply
    {
        if (! $this->hasDraft()) {
            return null;
        }

        $changes = $this->refreshDraft();

        if (! $this->hasDraft()) {
            return new ChatReply(implode(' ', $changes).' Tu pedido quedó vacío.', isOrderFlow: true);
        }

        return $this->summaryReply(trim('Retomemos tu pedido. '.implode(' ', $changes)));
    }

    /**
     * @return array{stage: string, items: array<int|string, int>, customer: array<string, string>, key: string|null, prices: array<int|string, string>}
     */
    private function draft(): array
    {
        return ($this->conversation->draft ?? []) + [
            'stage' => self::BROWSING,
            'items' => [],
            'customer' => [],
            'key' => null,
            'prices' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function save(array $changes): void
    {
        $this->conversation->update(['draft' => [...$this->draft(), ...$changes]]);
    }

    private function add(string $message): ChatReply
    {
        if ($reason = $this->context->orderBlockReason()) {
            return new ChatReply($reason, isOrderFlow: true);
        }

        $items = $this->draft()['items'];
        $added = [];

        foreach (preg_split('/\s+y\s+|,/', $message) ?: [] as $segment) {
            $matches = $this->context->matchProducts($segment);

            if ($matches->count() > 1) {
                return new ChatReply(
                    '¿Cuál de estas opciones quieres?',
                    suggestions: $matches->take(5)->map(fn (Product $product) => "Agrega {$product->name}")->all(),
                    isOrderFlow: true,
                );
            }

            /** @var Product|null $product */
            $product = $matches->first();

            if (! $product) {
                continue;
            }

            if (! $product->isOrderable()) {
                return new ChatReply("{$product->name} no está disponible en este momento.", isOrderFlow: true);
            }

            $quantity = min(($items[$product->id] ?? 0) + $this->quantity($segment), PlaceOrder::MAX_QUANTITY);
            $items[$product->id] = $quantity;
            $added[] = $product->name;
        }

        if ($added === []) {
            return new ChatReply(
                '¿Qué producto deseas agregar? Escribe, por ejemplo, "agrega 2 '.($this->context->products()->first()?->name ?? 'productos').'".',
                suggestions: ['Ver menú'],
                isOrderFlow: true,
            );
        }

        $this->save(['items' => $items, 'stage' => self::BROWSING, 'key' => null]);

        return $this->summaryReply('Agregué '.implode(', ', $added).' a tu pedido.');
    }

    private function remove(string $message): ChatReply
    {
        $items = $this->draft()['items'];
        $product = $this->itemInDraft($message);

        if (! $product) {
            return $this->summaryReply('Ese producto no está en tu pedido.');
        }

        unset($items[$product->id]);
        $this->save(['items' => $items, 'stage' => self::BROWSING, 'key' => null]);

        if ($items === []) {
            return new ChatReply('Quité '.$product->name.'. Tu pedido quedó vacío.', suggestions: ['Ver menú'], isOrderFlow: true);
        }

        return $this->summaryReply("Quité {$product->name} de tu pedido.");
    }

    private function setQuantity(string $message): ChatReply
    {
        $product = $this->itemInDraft($message);
        $quantity = $this->quantity($message, null);

        if (! $product || $quantity === null) {
            return $this->summaryReply('Indica el producto y la cantidad. Por ejemplo: "cambia gaseosa a 3".');
        }

        if ($quantity < 1 || $quantity > PlaceOrder::MAX_QUANTITY) {
            return $this->summaryReply('La cantidad debe estar entre 1 y '.PlaceOrder::MAX_QUANTITY.'.');
        }

        $items = $this->draft()['items'];
        $items[$product->id] = $quantity;
        $this->save(['items' => $items, 'stage' => self::BROWSING, 'key' => null]);

        return $this->summaryReply("Actualicé {$product->name} a {$quantity}.");
    }

    private function itemInDraft(string $message): ?Product
    {
        $ids = array_map('intval', array_keys($this->draft()['items']));

        return $this->context->matchProducts($message)->first(fn (Product $product) => in_array($product->id, $ids, true));
    }

    private function askCustomerData(): ChatReply
    {
        $changes = $this->refreshDraft();

        if (! $this->hasDraft()) {
            return new ChatReply(implode(' ', $changes).' Tu pedido quedó vacío.', isOrderFlow: true);
        }

        $this->save(['stage' => self::NAME]);

        return new ChatReply(trim(implode(' ', $changes).' ¿A nombre de quién registramos el pedido?'), isOrderFlow: true);
    }

    private function collect(string $stage, string $raw): ChatReply
    {
        $value = trim($raw);
        $customer = $this->draft()['customer'];

        if ($stage === self::NAME) {
            if ($value === '' || mb_strlen($value) > 150) {
                return new ChatReply('Escribe un nombre válido, por favor.', isOrderFlow: true);
            }

            $this->save(['customer' => [...$customer, 'customer_name' => $value], 'stage' => self::PHONE]);

            return new ChatReply('¿Cuál es tu número de teléfono?', isOrderFlow: true);
        }

        if ($stage === self::PHONE) {
            if (! preg_match(PlaceOrder::PHONE_REGEX, $value)) {
                return new ChatReply('Ese teléfono no parece válido. Escríbelo de nuevo, por favor.', isOrderFlow: true);
            }

            $this->save(['customer' => [...$customer, 'customer_phone' => $value], 'stage' => self::ADDRESS]);

            return new ChatReply('¿Cuál es la dirección de entrega?', isOrderFlow: true);
        }

        if ($value === '' || mb_strlen($value) > 255) {
            return new ChatReply('Escribe una dirección válida, por favor.', isOrderFlow: true);
        }

        $this->save([
            'customer' => [...$customer, 'delivery_address' => $value],
            'stage' => self::REVIEW,
            'key' => 'chat-'.Str::uuid(),
        ]);

        return $this->reviewReply();
    }

    private function review(string $message): ChatReply
    {
        if (preg_match('/^(si|si confirmo|confirmo|confirmar|confirmar pedido)$/', $message)) {
            return $this->confirm();
        }

        if (preg_match('/^(no|corregir|modificar|cambiar|seguir comprando)\b/', $message)) {
            $this->save(['stage' => self::BROWSING, 'key' => null]);

            return $this->summaryReply('De acuerdo. Puedes agregar, quitar o cambiar productos.');
        }

        return $this->reviewReply('Para registrar tu pedido necesito tu confirmación.');
    }

    private function confirm(): ChatReply
    {
        $draft = $this->draft();

        try {
            $order = $this->placeOrder->handle(
                $this->context->establishment->id,
                $draft['items'],
                $draft['customer'],
                Auth::id(),
                'chat',
                $draft['key'],
            );
        } catch (ValidationException $exception) {
            $this->save(['stage' => self::BROWSING, 'key' => null]);

            return $this->summaryReply($exception->validator->errors()->first().' Revisa tu pedido antes de continuar.');
        }

        $this->conversation->update(['draft' => null]);

        return new ChatReply(
            "¡Listo! Tu pedido #{$order->id} fue registrado. Total: ".EstablishmentContext::money($order->total).'.',
            link: route('orders.track', $order->public_token),
            isOrderFlow: true,
        );
    }

    private function cancel(): ChatReply
    {
        $this->conversation->update(['draft' => null]);

        return new ChatReply('Cancelé tu pedido. No se registró nada.', suggestions: ['Ver menú'], isOrderFlow: true);
    }

    private function summaryReply(string $text): ChatReply
    {
        return new ChatReply($text, summary: $this->summary(), suggestions: ['Listo, continuar', 'Ver menú', 'Cancelar pedido'], isOrderFlow: true);
    }

    private function reviewReply(string $intro = 'Revisa tu pedido:'): ChatReply
    {
        $customer = $this->draft()['customer'];

        return new ChatReply(
            "{$intro}\nA nombre de {$customer['customer_name']}, teléfono {$customer['customer_phone']}, entrega en {$customer['delivery_address']}.",
            summary: $this->summary(),
            suggestions: ['Confirmar pedido', 'Modificar', 'Cancelar pedido'],
            isOrderFlow: true,
        );
    }

    /**
     * @return array{lines: array<int, array{name: string, quantity: int, subtotal: string}>, total: string}
     */
    private function summary(): array
    {
        $lines = [];
        $total = 0.0;

        foreach ($this->draft()['items'] as $id => $quantity) {
            $product = $this->context->products()->firstWhere('id', (int) $id);

            if (! $product) {
                continue;
            }

            $subtotal = round((float) $product->price * $quantity, 2);
            $total += $subtotal;
            $lines[] = ['name' => $product->name, 'quantity' => $quantity, 'subtotal' => EstablishmentContext::money($subtotal)];
        }

        return ['lines' => $lines, 'total' => EstablishmentContext::money($total)];
    }

    /**
     * Quita productos que dejaron de estar disponibles e informa cambios de precio.
     *
     * @return array<int, string>
     */
    private function refreshDraft(): array
    {
        $draft = $this->draft();
        $items = $draft['items'];
        $prices = $draft['prices'];
        $changes = [];

        foreach ($items as $id => $quantity) {
            $product = $this->context->products()->firstWhere('id', (int) $id);

            if (! $product || ! $product->isOrderable()) {
                unset($items[$id], $prices[$id]);
                $changes[] = ($product?->name ?? 'Un producto').' ya no está disponible.';

                continue;
            }

            if (isset($prices[$id]) && (float) $prices[$id] !== (float) $product->price) {
                $changes[] = "El precio de {$product->name} cambió a ".EstablishmentContext::money($product->price).'.';
            }

            $prices[$id] = $product->price;
        }

        $this->save(['items' => $items, 'prices' => $prices]);

        return $changes;
    }

    private function quantity(string $text, ?int $default = 1): ?int
    {
        if (preg_match('/(?<![\d.])-?\d+/', $text, $match)) {
            return (int) $match[0];
        }

        foreach (explode(' ', $text) as $word) {
            if (isset(self::NUMBERS[$word])) {
                return self::NUMBERS[$word];
            }
        }

        return $default;
    }
}
