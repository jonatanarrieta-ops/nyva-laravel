<?php

use App\Models\Category;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('the three modules work together from the platform to the reports', function () {
    $platform = User::factory()->create(['role' => 'platform_admin']);

    Livewire::actingAs($platform)
        ->test('pages::admin.plans')
        ->call('create')
        ->set('name', 'Completo')
        ->set('monthly_price', '50000')
        ->set('features', array_keys(Plan::FEATURES))
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($platform)
        ->test('pages::admin.establishments')
        ->call('create')
        ->set('name', 'Burger Demo')
        ->set('type', 'Cocina oculta')
        ->set('address', 'Calle 1 # 2-3')
        ->set('phone', '3001234567')
        ->set('ownerFirstName', 'Ana')
        ->set('ownerLastName', 'Ruiz')
        ->set('ownerEmail', 'ana@example.com')
        ->set('ownerPassword', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $establishment = Establishment::firstOrFail();
    $owner = $establishment->user;

    Livewire::actingAs($platform)
        ->test('pages::admin.subscriptions')
        ->call('create')
        ->set('establishment_id', (string) $establishment->id)
        ->set('plan_id', (string) Plan::firstOrFail()->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->actingAs($owner)
        ->post(route('categories.store'), [
            'establishment_id' => $establishment->id,
            'name' => 'Hamburguesas',
            'status' => '1',
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('products.store'), [
        'category_id' => Category::firstOrFail()->id,
        'name' => 'Hamburguesa clásica',
        'price' => '25000',
        'available' => '1',
        'status' => '1',
    ])->assertSessionHasNoErrors();

    Auth::logout();

    $this->get(route('catalog.show', 'burger-demo'))
        ->assertOk()
        ->assertSee('Hamburguesa clásica')
        ->assertSee('Asistente');

    $chat = Livewire::test('pages::chatbot.widget', ['establishmentId' => $establishment->id]);

    foreach (['quiero 2 hamburguesa clasica', 'listo', 'Cliente Demo', '3009876543', 'Carrera 7 # 8-9', 'confirmar pedido'] as $message) {
        $chat->call('quick', $message)->assertHasNoErrors();
    }

    $order = Order::firstOrFail();

    expect($order->status)->toBe('pending')
        ->and($order->user_id)->toBeNull()
        ->and((float) $order->total)->toBe(50000.0)
        ->and($owner->notifications()->count())->toBe(1);

    $orders = Livewire::actingAs($owner)->test('orders.index')->assertSee('Cliente Demo');

    foreach (['confirmed', 'preparing', 'shipped', 'delivered'] as $status) {
        $orders->call('updateStatus', $order->id, $status);

        expect($order->fresh()->status)->toBe($status);
    }

    Auth::logout();

    $this->get(route('orders.track', $order->public_token))
        ->assertOk()
        ->assertSee('Entregado')
        ->assertSeeInOrder(['data-step="delivered"', 'data-reached="true"'], false);

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('orderCount', 1)
        ->assertViewHas('completedOrderCount', 1)
        ->assertViewHas('salesToday', fn ($sales) => (float) $sales === 50000.0);

    $report = Livewire::actingAs($owner)->test('pages::reports.index')->instance();

    expect($report->summary['orders'])->toBe(1)
        ->and((float) $report->summary['sales'])->toBe(50000.0)
        ->and((int) $report->summary['units'])->toBe(2)
        ->and($report->topProducts->first()->name)->toBe('Hamburguesa clásica')
        ->and($report->topEstablishments->first()->name)->toBe('Burger Demo');

    $this->actingAs($platform)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Burger Demo')
        ->assertSee('50.000');
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
