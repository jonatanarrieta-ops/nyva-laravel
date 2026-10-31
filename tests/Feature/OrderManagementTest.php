<?php

use App\Actions\PlaceOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

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

    Livewire::actingAs($admin)
        ->test('orders.index')
        ->call('openCreateForm')
        ->set('establishment_id', $establishment->id)
        ->set('customer_name', 'Ana')
        ->set('customer_phone', '3001234567')
        ->set('delivery_address', 'Calle 1')
        ->call('addProduct', $product->id)
        ->set("cart.{$product->id}.price", 1)
        ->call('saveOrder')
        ->assertHasNoErrors();

    expect((float) Order::firstOrFail()->total)->toBe(25000.0);
});

test('admin form requires the delivery address', function () {
    [$admin, $establishment, , $product] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('orders.index')
        ->call('openCreateForm')
        ->set('establishment_id', $establishment->id)
        ->set('customer_name', 'Ana')
        ->set('customer_phone', '3001234567')
        ->call('addProduct', $product->id)
        ->call('saveOrder')
        ->assertHasErrors('delivery_address');

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

    Livewire::actingAs($admin)
        ->test('orders.index')
        ->call('updateStatus', $order->id, $to);

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
    [, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id);
    $other = User::factory()->create(['role' => 'establishment_admin']);

    expect(fn () => Livewire::actingAs($other)
        ->test('orders.index')
        ->call('updateStatus', $order->id, 'confirmed'))
        ->toThrow(ModelNotFoundException::class);

    expect($order->fresh()->status)->toBe('pending');
});

test('foreign orders are not listed nor opened from the url', function () {
    [, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id);
    $other = User::factory()->create(['role' => 'establishment_admin']);

    $this->actingAs($other)
        ->get(route('orders.index', ['order' => $order->id]))
        ->assertOk()
        ->assertDontSee('Calle de prueba');
});
