<?php

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

$customer = [
    'customer_name' => 'Ana Ruiz',
    'customer_phone' => '3001234567',
    'delivery_address' => 'Calle 10 # 20-30 apto 501',
];

test('guests can place an order and follow it with its link', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);

    $response = $this->post(route('checkout.store'), [
        'order_key' => (string) Str::uuid(),
        'customer_name' => 'Ana Ruiz',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 10',
    ]);

    $order = Order::firstOrFail();

    $response->assertRedirect(route('orders.track', $order->public_token));

    expect($order->user_id)->toBeNull();
});

test('tracking tokens are long, random and unique', function () {
    [, $establishment] = catalogFixture();
    $tokens = collect(range(1, 20))->map(fn () => makeOrder($establishment->id)->public_token);

    expect($tokens->unique())->toHaveCount(20);

    $tokens->each(fn ($token) => expect($token)->toMatch('/^[A-Za-z0-9]{40}$/'));
});

test('the public link shows masked data and protects itself', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    $order = app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], $customer);

    $this->get(route('orders.track', $order->public_token))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee('*******567')
        ->assertDontSee('3001234567')
        ->assertDontSee('apto 501')
        ->assertDontSee('Ana Ruiz');
});

test('the public link cannot modify an order', function () {
    [, $establishment] = catalogFixture();
    $order = makeOrder($establishment->id);

    $component = Livewire::test('pages::tracking.show', ['token' => $order->public_token]);

    foreach (['updateStatus', 'cancel', 'save'] as $method) {
        expect(fn () => $component->call($method, 'cancelled'))->toThrow(Exception::class);
    }

    expect(fn () => Livewire::test('pages::tracking.show', ['token' => $order->public_token])->set('token', 'x'))
        ->toThrow(Exception::class);

    expect($order->fresh()->status)->toBe('pending');
});

test('invalid tracking tokens are not found', function () {
    $this->get('/pedido/'.str_repeat('a', 40))->assertNotFound();
    $this->get('/pedido/corto')->assertNotFound();
});

test('sending the same order twice does not duplicate it', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    $place = fn () => app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], $customer, null, 'web', 'clave-unica');

    expect($place()->id)->toBe($place()->id)
        ->and(Order::count())->toBe(1);
});

test('suspended or closed establishments refuse public orders', function (string $column, string $value) use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    $establishment->forceFill([$column => $value])->save();

    expect(fn () => app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], $customer))
        ->toThrow(ValidationException::class);

    expect(Order::count())->toBe(0);
})->with([
    ['status', 'suspended'],
    ['status', 'inactive'],
    ['operating_status', 'closed'],
    ['operating_status', 'temporarily_inactive'],
]);

test('an establishment without a subscription keeps its page but refuses orders', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    $establishment->subscriptions()->delete();

    $this->get(route('catalog.show', $establishment))->assertOk();

    expect(fn () => app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], $customer))
        ->toThrow(ValidationException::class);
});

test('the cart refuses products of a closed establishment', function () {
    [, $establishment, , $product] = catalogFixture();
    $establishment->update(['operating_status' => 'closed']);

    expect(app(Cart::class)->add($product))->toBe(Cart::CLOSED);
});

test('panel orders do not depend on the public schedule', function () use ($customer) {
    [, $establishment, , $product] = catalogFixture();
    $establishment->update(['operating_status' => 'closed']);

    $order = app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], $customer, channel: 'panel');

    expect($order->channel)->toBe('panel');
});

test('businesses cannot be discovered by their number', function () {
    [, $establishment] = catalogFixture();

    $this->get('/establecimientos/'.$establishment->id)->assertNotFound();
});

test('an owner cannot switch to a foreign establishment', function () {
    [$owner] = catalogFixture();
    [, $foreign] = catalogFixture();

    expect(fn () => Livewire::actingAs($owner)
        ->test('pages::faqs.index')
        ->set('currentEstablishmentId', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

test('owners only see their own data in every list', function (string $component) {
    [$owner] = catalogFixture();
    [, $foreign] = catalogFixture();
    $foreign->update(['name' => 'Negocio ajeno']);
    $foreign->categories()->update(['name' => 'Categoría ajena']);

    Livewire::actingAs($owner)
        ->test($component)
        ->assertDontSee('Negocio ajeno')
        ->assertDontSee('Categoría ajena');
})->with(['pages::categories.index', 'pages::products.index', 'orders.index', 'pages::reports.index']);

test('users cannot raise their own role from the profile', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('first_name', 'Nuevo')
        ->call('updateProfileInformation');

    expect($user->fresh()->role)->toBe('customer');
});

test('a missing establishment is not public', function () {
    expect(Establishment::where('slug', 'no-existe')->exists())->toBeFalse();

    $this->get('/establecimiento/no-existe')->assertNotFound();
});
