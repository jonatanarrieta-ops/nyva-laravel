<?php

use App\Actions\PlaceOrder;
use App\Models\Category;
use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function staffFor(Establishment $establishment, array $permissions): User
{
    $user = User::factory()->create(['role' => 'establishment_staff']);
    $establishment->members()->attach($user->id, ['permissions' => json_encode($permissions)]);

    return $user;
}

test('the owner updates the public information of the establishment', function () {
    Storage::fake('public');
    [$owner, $establishment] = catalogFixture();

    Livewire::actingAs($owner)
        ->test('pages::establishments.index')
        ->set('name', 'Nuevo nombre')
        ->set('slug', 'nuevo-enlace')
        ->set('operating_status', 'temporarily_inactive')
        ->set('logo', UploadedFile::fake()->create('logo.png', 100, 'image/png'))
        ->call('save')
        ->assertHasNoErrors();

    $establishment->refresh();

    expect($establishment->name)->toBe('Nuevo nombre')
        ->and($establishment->slug)->toBe('nuevo-enlace')
        ->and($establishment->operating_status)->toBe('temporarily_inactive')
        ->and($establishment->canReceiveOrders())->toBeFalse();

    Storage::disk('public')->assertExists($establishment->logo);

    $this->get(route('catalog.show', 'nuevo-enlace'))->assertOk()->assertSee('Nuevo nombre');
});

test('establishment data is validated and the owner cannot change the platform status', function () {
    [$owner, $establishment] = catalogFixture();
    [, $other] = catalogFixture();

    Livewire::actingAs($owner)
        ->test('pages::establishments.index')
        ->set('name', '')
        ->set('phone', 'abc')
        ->set('email', 'mal')
        ->set('slug', $other->slug)
        ->call('save')
        ->assertHasErrors(['name', 'phone', 'email', 'slug']);

    expect($establishment->fresh()->status)->toBe('active');
});

test('an owner without establishments sees a clear message', function () {
    $owner = User::factory()->create(['role' => 'establishment_admin']);

    $this->actingAs($owner)->get(route('establishments.index'))->assertOk()->assertSee('no tiene un establecimiento');
});

test('schedules are saved and control the orders', function () {
    [$owner, $establishment] = catalogFixture();
    $today = strtolower(now()->format('l'));

    $component = Livewire::actingAs($owner)->test('pages::schedules.index');

    foreach (array_keys(Schedule::DAYS) as $day) {
        $component->set("days.$day.enabled", false);
    }

    $component->set("days.$today.enabled", true)
        ->set("days.$today.opens_at", '00:00')
        ->set("days.$today.closes_at", '23:59')
        ->call('save')
        ->assertHasNoErrors();

    expect($establishment->schedules()->count())->toBe(7)
        ->and($establishment->fresh()->isWithinSchedule())->toBeTrue();

    $component->set("days.$today.enabled", false)
        ->set('days.'.strtolower(now()->addDays(2)->format('l')).'.enabled', true)
        ->call('save');

    expect($establishment->fresh()->canReceiveOrders())->toBeFalse();
});

test('invalid schedules are rejected', function () {
    [$owner] = catalogFixture();

    Livewire::actingAs($owner)
        ->test('pages::schedules.index')
        ->set('days.monday.opens_at', 'nada')
        ->call('save')
        ->assertHasErrors('days.monday.opens_at');
});

test('overnight schedules work', function () {
    $schedule = new Schedule(['day_of_week' => 'friday', 'opens_at' => '20:00:00', 'closes_at' => '02:00:00']);

    expect($schedule->isOpenAt(now()->parse('next friday 21:00')))->toBeTrue()
        ->and($schedule->isOpenAt(now()->parse('next saturday 01:00')))->toBeTrue()
        ->and($schedule->isOpenAt(now()->parse('next saturday 03:00')))->toBeFalse();
});

test('faqs are managed per establishment', function () {
    [$owner, $establishment] = catalogFixture();
    [, $other] = catalogFixture();
    $foreign = $other->frequentlyAskedQuestions()->create(['question' => 'Ajena', 'answer' => 'Ajena', 'status' => 'active']);

    $component = Livewire::actingAs($owner)
        ->test('pages::faqs.index')
        ->call('create')
        ->set('question', '¿Tienen domicilio?')
        ->set('answer', 'Sí.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDontSee('Ajena');

    expect($establishment->frequentlyAskedQuestions()->count())->toBe(1);

    expect(fn () => $component->call('delete', $foreign->id))
        ->toThrow(ModelNotFoundException::class);
});

test('the chatbot settings are saved', function () {
    [$owner, $establishment] = catalogFixture();

    Livewire::actingAs($owner)
        ->test('pages::chatbot.settings')
        ->set('enabled', false)
        ->set('welcome_message', 'Bienvenido')
        ->set('business_info', 'Aceptamos efectivo')
        ->call('save')
        ->assertHasNoErrors();

    expect($establishment->chatbot())
        ->enabled->toBeFalse()
        ->welcome_message->toBe('Bienvenido');
});

test('the owner adds staff with permissions', function () {
    [$owner, $establishment] = catalogFixture();

    Livewire::actingAs($owner)
        ->test('pages::team.index')
        ->set('first_name', 'Luis')
        ->set('last_name', 'Paz')
        ->set('email', 'luis@example.com')
        ->set('password', 'password123')
        ->set('permissions', ['orders'])
        ->call('add')
        ->assertHasNoErrors();

    $staff = User::where('email', 'luis@example.com')->firstOrFail();

    expect($staff->role)->toBe('establishment_staff')
        ->and($staff->accessibleEstablishments('orders')->whereKey($establishment->id)->exists())->toBeTrue()
        ->and($staff->accessibleEstablishments('catalog')->exists())->toBeFalse();
});

test('staff only reach what their permissions allow', function () {
    [, $establishment] = catalogFixture();
    $staff = staffFor($establishment, ['orders']);

    $this->actingAs($staff)->get(route('orders.index'))->assertOk();
    $this->actingAs($staff)->get(route('products.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('chatbot.settings'))->assertForbidden();
});

test('staff with settings cannot manage the team', function () {
    [, $establishment] = catalogFixture();
    $staff = staffFor($establishment, ['settings']);

    Livewire::actingAs($staff)
        ->test('pages::team.index')
        ->set('first_name', 'A')
        ->call('add')
        ->assertForbidden();
});

test('the plan limits products on the server', function () {
    [$owner, $establishment, $category] = catalogFixture();
    $establishment->currentPlan()->update(['product_limit' => 1]);

    $this->actingAs($owner)
        ->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Excedente',
            'price' => '1000',
        ])
        ->assertSessionHasErrors('category_id');

    expect(Product::where('name', 'Excedente')->exists())->toBeFalse();
});

test('reducing a plan keeps the existing products', function () {
    [$owner, $establishment, , $product] = catalogFixture();
    $establishment->currentPlan()->update(['product_limit' => 1]);

    $this->actingAs($owner)
        ->put(route('products.update', $product->id), [
            'category_id' => $product->category_id,
            'name' => 'Editado',
            'price' => (string) $product->price,
            'available' => '1',
            'status' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($product->fresh()->name)->toBe('Editado');
});

test('the plan limits users', function () {
    [$owner, $establishment] = catalogFixture();
    $establishment->currentPlan()->update(['user_limit' => 1]);

    Livewire::actingAs($owner)
        ->test('pages::team.index')
        ->set('first_name', 'Luis')
        ->set('last_name', 'Paz')
        ->set('email', 'luis@example.com')
        ->set('password', 'password123')
        ->call('add')
        ->assertHasErrors('email');

    expect(User::where('email', 'luis@example.com')->exists())->toBeFalse();
});

test('a plan without reports blocks that module', function () {
    [$owner, $establishment] = catalogFixture();
    $establishment->currentPlan()->update(['features' => ['public_order']]);

    $this->actingAs($owner)->get(route('reports.index'))->assertForbidden();
});

test('a plan without team blocks new users', function () {
    [$owner, $establishment] = catalogFixture();
    $establishment->currentPlan()->update(['features' => ['public_order']]);

    Livewire::actingAs($owner)
        ->test('pages::team.index')
        ->set('first_name', 'Luis')
        ->call('add')
        ->assertHasErrors('email');
});

test('owners and staff with order permission are notified of new orders', function () {
    Notification::fake();
    [$owner, $establishment, , $product] = catalogFixture();
    $staff = staffFor($establishment, ['orders']);
    $catalogStaff = staffFor($establishment, ['catalog']);

    app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], [
        'customer_name' => 'Ana',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 1',
    ]);

    Notification::assertSentTo([$owner, $staff], NewOrderReceived::class);
    Notification::assertNotSentTo($catalogStaff, NewOrderReceived::class);
});

test('the bell only shows the notifications of the current user', function () {
    [$owner, $establishment, , $product] = catalogFixture();
    [$other] = catalogFixture();

    app(PlaceOrder::class)->handle($establishment->id, [$product->id => 1], [
        'customer_name' => 'Ana',
        'customer_phone' => '3001234567',
        'delivery_address' => 'Calle 1',
    ]);

    $id = $owner->notifications()->firstOrFail()->id;

    Livewire::actingAs($owner)->test('pages::notifications.bell')->call('toggle')->assertSee('Nuevo pedido');

    expect(fn () => Livewire::actingAs($other)->test('pages::notifications.bell')->call('markAsRead', $id))
        ->toThrow(ModelNotFoundException::class);

    Livewire::actingAs($owner)->test('pages::notifications.bell')->call('markAllAsRead');

    expect($owner->unreadNotifications()->count())->toBe(0);
});

test('expiring subscriptions alert once', function () {
    [$owner, $establishment] = catalogFixture();
    $admin = User::factory()->create(['role' => 'platform_admin']);
    $establishment->subscriptions()->update(['end_date' => now()->addDays(3)]);

    $this->artisan('nyva:check-subscriptions')->assertSuccessful();
    $this->artisan('nyva:check-subscriptions')->assertSuccessful();

    expect($admin->notifications()->count())->toBe(1)
        ->and($owner->notifications()->count())->toBe(1);
});

test('an inactive category and plan data do not leak', function () {
    [, $establishment, $category] = catalogFixture();
    Category::whereKey($category->id)->update(['status' => 'inactive']);
    $plan = Plan::first();

    $this->get(route('catalog.show', $establishment))
        ->assertDontSee($plan->name)
        ->assertDontSee($establishment->user->email);
});
