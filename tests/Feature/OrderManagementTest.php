<?php

use App\Actions\PlaceOrder;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

$customer = [
    'customer_name' => 'Cliente Demo',
    'customer_phone' => '3001234567',
    'delivery_address' => 'Calle 10 # 20-30',
];

test('placing an order uses database prices and recalculates totals', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();

    $order = app(PlaceOrder::class)->handle($establishment->id, [$product->id => 3], $customer);

    expect((float) $order->total)->toBe(75000.0)
        ->and($order->status)->toBe('pending')
        ->and($order->orderItems)->toHaveCount(1)
        ->and((float) $order->orderItems->first()->unit_price)->toBe(25000.0);
});

test('admin form ignores a manipulated price', function () {
    [$admin, $establishment, , $product] = catalogFixture();

    $this->actingAs($admin)
        ->get(route('orders.create', ['establishment' => $establishment->id]))
        ->assertOk()
        ->assertSee($product->name);

    $response = $this->post(route('orders.store'), [
        'establishment_id' => $establishment->id,
        'customer_name' => 'Ana',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 1',
        'items' => [$product->id => 1],
        'price' => 1,
        'total' => 1,
    ]);

    $order = Order::firstOrFail();

    $response->assertRedirect(route('orders.show', $order->id));

    expect((float) $order->total)->toBe(25000.0)
        ->and($order->channel)->toBe('panel');
});

test('admin form requires the delivery address', function () {
    [$admin, $establishment, , $product] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('orders.store'), [
            'establishment_id' => $establishment->id,
            'customer_name' => 'Ana',
            'customer_phone' => '3001234567',
            'items' => [$product->id => 1],
        ])
        ->assertSessionHasErrors('delivery_address');

    $this->post(route('orders.store'), [
        'establishment_id' => $establishment->id,
        'customer_name' => 'Ana',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 1',
        'items' => [$product->id => 0],
    ])->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0);
});

test('invalid orders are rejected without leaving records', function (Closure $input) use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    [, , , $foreignProduct] = catalogFixture();
    $unavailable = Product::create([
        'category_id' => $product->category_id,
        'name' => 'Agotado',
        'price' => 100,
        'available' => false,
        'status' => 'active',
    ]);

    [$establishmentId, $quantities] = $input($establishment, $product, $foreignProduct, $unavailable);

    expect(fn () => app(PlaceOrder::class)->handle($establishmentId, $quantities, $customer))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0);
})->with([
    'sin productos' => fn ($e) => [$e->id, []],
    'cantidad cero' => fn ($e, $p) => [$e->id, [$p->id => 0]],
    'cantidad negativa' => fn ($e, $p) => [$e->id, [$p->id => -2]],
    'cantidad decimal' => fn ($e, $p) => [$e->id, [$p->id => '1.5']],
    'cantidad excesiva' => fn ($e, $p) => [$e->id, [$p->id => 100]],
    'producto inexistente' => fn ($e) => [$e->id, [999999 => 1]],
    'producto ajeno' => fn ($e, $p, $f) => [$e->id, [$f->id => 1]],
    'producto no disponible' => fn ($e, $p, $f, $u) => [$e->id, [$u->id => 1]],
    'establecimiento inexistente' => fn ($e, $p) => [999999, [$p->id => 1]],
]);

test('customer data is validated on the server', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();

    expect(fn () => app(PlaceOrder::class)->handle(
        $establishment->id,
        [$product->id => 1],
        [...$customer, 'customer_phone' => 'abc'],
    ))->toThrow(ValidationException::class);
});

test('orders only follow the allowed status transitions', function (string $from, string $to, bool $allowed) {
    [$admin, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id, $from);

    $this->actingAs($admin)
        ->patch(route('orders.update', $order->id), ['status' => $to])
        ->assertSessionHas($allowed ? 'success' : 'error');

    expect($order->fresh()->status)->toBe($allowed ? $to : $from);
})->with([
    ['pending', 'confirmed', true],
    ['pending', 'cancelled', true],
    ['confirmed', 'preparing', true],
    ['preparing', 'shipped', true],
    ['preparing', 'cancelled', true],
    ['shipped', 'delivered', true],
    ['pending', 'delivered', false],
    ['pending', 'shipped', false],
    ['confirmed', 'delivered', false],
    ['shipped', 'cancelled', false],
    ['delivered', 'pending', false],
    ['cancelled', 'confirmed', false],
]);

test('another owner cannot change a foreign order', function () {
    [, $establishment, , $product] = catalogFixture();
    $order = makeOrder($establishment->id);
    [$other] = catalogFixture();

    $this->actingAs($other)
        ->patch(route('orders.update', $order->id), ['status' => 'confirmed'])
        ->assertNotFound();

    $this->post(route('orders.store'), [
        'establishment_id' => $establishment->id,
        'customer_name' => 'Ana',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 1',
        'items' => [$product->id => 1],
    ])->assertSessionHasErrors('establishment_id');

    expect($order->fresh()->status)->toBe('pending')
        ->and(Order::count())->toBe(1);
});

test('foreign orders are not listed nor opened from the url', function () {
    [, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id);
    [$other] = catalogFixture();

    $this->actingAs($other)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertDontSee('Calle de prueba');

    $this->get(route('orders.show', $order->id))->assertNotFound();
});

test('the owner sees the detail of its own order', function () {
    [$admin, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id);

    $this->actingAs($admin)
        ->get(route('orders.show', $order->id))
        ->assertOk()
        ->assertSee("Pedido #{$order->id}")
        ->assertSee('Calle de prueba');
});
