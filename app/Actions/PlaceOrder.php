<?php

namespace App\Actions;

use App\Models\Establishment;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public const MAX_QUANTITY = 99;

    public const PHONE_REGEX = '/^[0-9+()\s-]{7,20}$/';

    /**
     * Crea el pedido tomando precios y disponibilidad de la base de datos.
     *
     * @param  array<int|string, mixed>  $quantities  Cantidades por id de producto.
     * @param  array{customer_name: string, customer_phone: string, delivery_address: string}  $customer
     *
     * @throws ValidationException
     */
    public function handle(
        int $establishmentId,
        array $quantities,
        array $customer,
        ?int $userId = null,
        string $channel = 'web',
        ?string $idempotencyKey = null,
    ): Order {
        if ($idempotencyKey !== null) {
            $existing = Order::where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }
        }

        $customer = Validator::make($customer, [
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'regex:'.self::PHONE_REGEX],
            'delivery_address' => ['required', 'string', 'max:255'],
        ], [
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_name.max' => 'El nombre no puede superar los 150 caracteres.',
            'customer_phone.required' => 'El teléfono del cliente es obligatorio.',
            'customer_phone.regex' => 'Ingresa un teléfono válido.',
            'delivery_address.required' => 'La dirección de entrega es obligatoria.',
            'delivery_address.max' => 'La dirección no puede superar los 255 caracteres.',
        ])->validate();

        $quantities = $this->validQuantities($quantities);

        $order = DB::transaction(function () use ($establishmentId, $quantities, $customer, $userId, $channel, $idempotencyKey) {
            $establishment = Establishment::query()
                ->whereKey($establishmentId)
                ->where('status', 'active')
                ->first();

            if (! $establishment) {
                $this->fail('El establecimiento no está disponible.');
            }

            // Los pedidos registrados desde el panel no dependen del horario público.
            if ($channel !== 'panel' && ($reason = $establishment->orderBlockReason())) {
                $this->fail($reason);
            }

            $products = Product::query()
                ->with('category')
                ->whereIn('id', array_keys($quantities))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $items = [];

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product || $product->category?->establishment_id !== $establishment->id) {
                    $this->fail('Uno o más productos no pertenecen al establecimiento.');
                }

                if (! $product->isOrderable()) {
                    $this->fail("El producto {$product->name} no está disponible.");
                }

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => round((float) $product->price * $quantity, 2),
                ];
            }

            $order = Order::create([
                ...$customer,
                'establishment_id' => $establishment->id,
                'user_id' => $userId,
                'channel' => $channel,
                'idempotency_key' => $idempotencyKey,
                'total' => round(array_sum(array_column($items, 'subtotal')), 2),
                'status' => 'pending',
                'ordered_at' => now(),
            ]);

            $order->orderItems()->createMany($items);

            return $order;
        });

        Notification::send($order->establishment->orderRecipients(), new NewOrderReceived($order));
        $order->user?->notify(new OrderConfirmed($order));

        return $order;
    }

    /**
     * @param  array<int|string, mixed>  $quantities
     * @return array<int, int>
     */
    private function validQuantities(array $quantities): array
    {
        if ($quantities === []) {
            $this->fail('Debes agregar al menos un producto al pedido.');
        }

        $valid = [];

        foreach ($quantities as $productId => $quantity) {
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => self::MAX_QUANTITY],
            ]);

            if ($quantity === false) {
                $this->fail('Las cantidades deben estar entre 1 y '.self::MAX_QUANTITY.'.');
            }

            $valid[(int) $productId] = $quantity;
        }

        return $valid;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['cart' => $message]);
    }
}
