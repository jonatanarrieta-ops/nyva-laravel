<?php

use App\Models\Category;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart;
use Livewire\Livewire;

test('the whole operation works from the admin to the reports', function () {
    $admin = User::factory()->create(['role' => 'establishment_admin']);
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::establishments.index')
        ->call('createEstablishment')
        ->set('name', 'Burger Demo')
        ->set('type', 'Restaurante')
        ->set('address', 'Calle 1 # 2-3')
        ->set('phone', '3001234567')
        ->call('save')
        ->assertHasNoErrors();

    $establishment = Establishment::firstOrFail();

    Livewire::actingAs($admin)
        ->test('pages::categories.index')
        ->call('createCategory')
        ->set('establishmentId', (string) $establishment->id)
        ->set('name', 'Hamburguesas')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)
        ->test('pages::products.index')
        ->call('createProduct')
        ->set('categoryId', (string) Category::firstOrFail()->id)
        ->set('name', 'Hamburguesa clásica')
        ->set('price', '25000')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::firstOrFail();

    Livewire::test('pages::catalog.show', ['establishment' => $establishment])
        ->assertSee('Hamburguesa clásica')
        ->call('addToCart', $product->id);

    Livewire::test('pages::cart.index')->call('increase', $product->id)->assertSee('$ 50.000');

    Livewire::actingAs($customer)
        ->test('pages::checkout.index')
        ->set('customer_phone', '3009876543')
        ->set('delivery_address', 'Carrera 7 # 8-9')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::firstOrFail();

    expect($order->status)->toBe('pending');

    $orders = Livewire::actingAs($admin)
        ->test('orders.index')
        ->assertSee($customer->first_name);

    foreach (['confirmed', 'preparing', 'shipped', 'delivered'] as $status) {
        $orders->call('updateStatus', $order->id, $status);

        expect($order->fresh()->status)->toBe($status);
    }

    $this->actingAs($customer)
        ->get(route('my-orders.show', $order->id))
        ->assertOk()
        ->assertSee('Entregado')
        ->assertSeeInOrder(['data-step="delivered"', 'data-reached="true"'], false);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('orderCount', 1)
        ->assertViewHas('ordersToday', 1)
        ->assertViewHas('salesToday', fn ($sales) => (float) $sales === 50000.0);

    $report = Livewire::actingAs($admin)->test('pages::reports.index')->instance();

    expect($report->summary['orders'])->toBe(1)
        ->and((float) $report->summary['sales'])->toBe(50000.0)
        ->and((int) $report->summary['units'])->toBe(2)
        ->and($report->topProducts->first()->name)->toBe('Hamburguesa clásica')
        ->and($report->topEstablishments->first()->name)->toBe('Burger Demo');

    expect(app(Cart::class)->count())->toBe(0);
});

test('cancelled orders do not count as sales', function () {
    [$admin, $establishment] = catalogFixture();
    makeOrder($establishment->id, 'delivered');
    makeOrder($establishment->id, 'cancelled');

    $report = Livewire::actingAs($admin)->test('pages::reports.index')->instance();

    expect($report->summary['orders'])->toBe(1)
        ->and((float) $report->summary['sales'])->toBe(1000.0);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertViewHas('salesToday', fn ($sales) => (float) $sales === 1000.0);
});

test('reports never include foreign establishments', function () {
    [$admin] = catalogFixture();
    [, $foreign] = catalogFixture();
    makeOrder($foreign->id, 'delivered');

    $report = Livewire::actingAs($admin)
        ->test('pages::reports.index')
        ->set('establishmentFilter', (string) $foreign->id)
        ->instance();

    expect($report->summary['orders'])->toBe(0);
});
