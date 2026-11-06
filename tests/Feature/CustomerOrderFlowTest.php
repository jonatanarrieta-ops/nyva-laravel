<?php

use App\Actions\PlaceOrder;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Support\Str;
use Livewire\Livewire;

function checkoutData(array $overrides = []): array
{
    return [
        'order_key' => (string) Str::uuid(),
        'customer_name' => 'Cliente Demo',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 10 # 20-30',
        ...$overrides,
    ];
}

test('guests can reach the checkout but not the private order history', function () {
    $this->get(route('checkout'))->assertRedirect(route('cart.index'));
    $this->get(route('my-orders.index'))->assertRedirect(route('login'));
});

test('an empty cart cannot reach the checkout', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('checkout'))
        ->assertRedirect(route('cart.index'));

    $this->post(route('checkout.store'), checkoutData())->assertRedirect(route('cart.index'));

    expect(Order::count())->toBe(0);
});

test('the checkout shows a review and creates a secure pending order', function () {
    [, $establishment, , $product] = catalogFixture();
    $customer = User::factory()->create();
    $cart = app(Cart::class);
    $cart->add($product);
    $cart->increase($product->id);

    $this->actingAs($customer)
        ->get(route('checkout'))
        ->assertOk()
        ->assertSee($establishment->name)
        ->assertSee($product->name)
        ->assertSee('$ 50.000');

    $response = $this->post(route('checkout.store'), checkoutData());

    $order = Order::with('orderItems')->firstOrFail();

    $response->assertRedirect(route('my-orders.confirmation', $order->id));

    expect($order->user_id)->toBe($customer->id)
        ->and($order->establishment_id)->toBe($establishment->id)
        ->and($order->status)->toBe('pending')
        ->and((float) $order->total)->toBe(50000.0)
        ->and($order->orderItems)->toHaveCount(1)
        ->and($order->orderItems->first()->quantity)->toBe(2)
        ->and($cart->quantities())->toBe([]);
});

test('checkout data is validated on the server', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);

    $this->actingAs(User::factory()->create())
        ->post(route('checkout.store'), checkoutData([
            'customer_name' => '',
            'customer_phone' => 'abc',
            'delivery_address' => '',
        ]))
        ->assertSessionHasErrors(['customer_name', 'customer_phone', 'delivery_address']);

    $this->post(route('checkout.store'), checkoutData(['order_key' => 'no-es-uuid']))
        ->assertSessionHasErrors('order_key');

    expect(Order::count())->toBe(0);
});

test('a product that becomes unavailable stops the order and keeps the cart', function () {
    [, , , $product] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    $product->update(['available' => false]);

    $this->actingAs(User::factory()->create())
        ->post(route('checkout.store'), checkoutData())
        ->assertSessionHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and($cart->quantities())->not->toBe([]);
});

test('the order uses the price at confirmation time', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);

    $this->actingAs(User::factory()->create())->get(route('checkout'))->assertSee('$ 25.000');

    $product->update(['price' => 40000]);

    $this->post(route('checkout.store'), checkoutData());

    expect((float) Order::firstOrFail()->total)->toBe(40000.0);
});

test('prices cannot be sent from the browser', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);

    $this->actingAs(User::factory()->create())
        ->post(route('checkout.store'), checkoutData(['total' => 1, 'price' => 1, 'unit_price' => 1]));

    expect((float) Order::firstOrFail()->total)->toBe(25000.0);
});

test('a failure while saving items rolls the order back', function () {
    [, $establishment, , $product] = catalogFixture();

    Order::created(function () {
        throw new RuntimeException('Fallo simulado');
    });

    try {
        app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], [
            'customer_name' => 'Ana',
            'customer_phone' => '3001234567',
            'delivery_address' => 'Calle 1',
        ]);
    } catch (RuntimeException) {
        // El fallo es parte de la prueba.
    }

    Order::flushEventListeners();

    expect(Order::count())->toBe(0);
});

test('the confirmation shows the order summary', function () {
    [, $establishment] = catalogFixture();
    $customer = User::factory()->create();
    $order = makeOrder($establishment->id, 'pending', $customer->id);

    $this->actingAs($customer)
        ->get(route('my-orders.confirmation', $order->id))
        ->assertOk()
        ->assertSee('Pedido realizado correctamente')
        ->assertSee("#{$order->id}")
        ->assertSee($establishment->name)
        ->assertSee('Pendiente');
});

test('customers only see their own orders', function () {
    [, $establishment] = catalogFixture();
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $mine = makeOrder($establishment->id, 'pending', $customer->id);
    $foreign = makeOrder($establishment->id, 'pending', $other->id);

    $this->actingAs($customer)
        ->get(route('my-orders.index'))
        ->assertOk()
        ->assertSee("Pedido #{$mine->id}")
        ->assertDontSee("Pedido #{$foreign->id}");

    $this->get(route('my-orders.show', $mine->id))->assertOk();
    $this->get(route('my-orders.show', $foreign->id))->assertNotFound();
    $this->get(route('my-orders.confirmation', $foreign->id))->assertNotFound();
    $this->get(route('my-orders.show', 999999))->assertNotFound();
    $this->get(route('my-orders.show', 'abc'))->assertNotFound();
});

test('tracking never marks future steps as completed', function () {
    [, $establishment] = catalogFixture();
    $customer = User::factory()->create();
    $order = makeOrder($establishment->id, 'preparing', $customer->id);

    $this->actingAs($customer)
        ->get(route('my-orders.show', $order->id))
        ->assertSee('data-step="preparing"', false)
        ->assertSeeInOrder(['data-step="pending"', 'data-reached="true"'], false)
        ->assertSeeInOrder(['data-step="confirmed"', 'data-reached="true"'], false)
        ->assertSeeInOrder(['data-step="preparing"', 'data-reached="true"'], false)
        ->assertSeeInOrder(['data-step="shipped"', 'data-reached="false"'], false)
        ->assertSeeInOrder(['data-step="delivered"', 'data-reached="false"'], false);
});

test('a cancelled order only shows the cancellation', function () {
    [, $establishment] = catalogFixture();
    $customer = User::factory()->create();
    $order = makeOrder($establishment->id, 'cancelled', $customer->id);

    $this->actingAs($customer)
        ->get(route('my-orders.show', $order->id))
        ->assertSee('Pedido cancelado')
        ->assertDontSee('data-step', false);
});

test('an order cannot be opened through a locked property change', function () {
    [, $establishment] = catalogFixture();
    $customer = User::factory()->create();
    $mine = makeOrder($establishment->id, 'pending', $customer->id);
    $foreign = makeOrder($establishment->id, 'pending', User::factory()->create()->id);

    expect(fn () => Livewire::actingAs($customer)
        ->test('pages::my-orders.show', ['order' => $mine->id])
        ->set('orderId', $foreign->id))
        ->toThrow(Exception::class);
});
