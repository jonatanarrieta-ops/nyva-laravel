<?php

use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

function platformAdmin(): User
{
    return User::factory()->create(['role' => 'platform_admin']);
}

test('only platform admins can open the platform panel', function (string $routeName) {
    [$owner] = catalogFixture();

    $this->actingAs($owner)->get(route($routeName))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route($routeName))->assertForbidden();
    $this->actingAs(platformAdmin())->get(route($routeName))->assertOk();
})->with(['admin.dashboard', 'admin.establishments', 'admin.users', 'admin.plans', 'admin.subscriptions', 'admin.statistics']);

test('platform admins are sent to their dashboard and cannot use a business panel', function () {
    $admin = platformAdmin();

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    $this->actingAs($admin)->get(route('products.index'))->assertForbidden();
});

test('the platform registers an establishment with a new owner', function () {
    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.establishments')
        ->call('create')
        ->set('name', 'Cocina Sur')
        ->set('type', 'Cocina oculta')
        ->set('address', 'Calle 1')
        ->set('phone', '3001234567')
        ->set('ownerFirstName', 'Ana')
        ->set('ownerLastName', 'Ruiz')
        ->set('ownerEmail', 'ana@example.com')
        ->set('ownerPassword', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $establishment = Establishment::firstOrFail();

    expect($establishment->slug)->toBe('cocina-sur')
        ->and($establishment->user->role)->toBe('establishment_admin')
        ->and($establishment->user->email)->toBe('ana@example.com');
});

test('establishment form rejects invalid data', function () {
    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.establishments')
        ->call('create')
        ->set('phone', 'abc')
        ->call('save')
        ->assertHasErrors(['name', 'type', 'address', 'phone']);

    expect(Establishment::count())->toBe(0);
});

test('suspending an establishment blocks it and alerts the owner', function () {
    [$owner, $establishment] = catalogFixture();

    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.establishments')
        ->call('setStatus', $establishment->id, 'suspended');

    expect($establishment->fresh()->canReceiveOrders())->toBeFalse()
        ->and($owner->notifications()->count())->toBe(1);

    $this->get(route('catalog.show', $establishment))->assertNotFound();
});

test('users are managed by the platform and cannot lock out the current admin', function () {
    $admin = platformAdmin();
    $customer = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleStatus', $customer->id)
        ->call('toggleStatus', $admin->id);

    expect($customer->fresh()->status)->toBe('inactive')
        ->and($admin->fresh()->status)->toBe('active');

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('edit', $admin->id)
        ->set('role', 'customer')
        ->call('save')
        ->assertHasErrors('role');

    expect($admin->fresh()->isPlatformAdmin())->toBeTrue();
});

test('invalid roles are rejected', function () {
    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.users')
        ->call('create')
        ->set('first_name', 'A')
        ->set('last_name', 'B')
        ->set('email', 'a@example.com')
        ->set('password', 'password123')
        ->set('role', 'superuser')
        ->call('save')
        ->assertHasErrors('role');
});

test('plans store price, duration, limits and features', function () {
    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.plans')
        ->call('create')
        ->set('name', 'Inicial')
        ->set('monthly_price', '30000')
        ->set('duration_days', '60')
        ->set('product_limit', '10')
        ->set('user_limit', '2')
        ->set('features', ['chatbot', 'public_order'])
        ->call('save')
        ->assertHasNoErrors();

    $plan = Plan::firstOrFail();

    expect($plan->duration_days)->toBe(60)
        ->and($plan->product_limit)->toBe(10)
        ->and($plan->user_limit)->toBe(2)
        ->and($plan->hasFeature('chatbot'))->toBeTrue()
        ->and($plan->hasFeature('reports'))->toBeFalse();
});

test('unknown plan features are rejected', function () {
    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.plans')
        ->call('create')
        ->set('name', 'Mal')
        ->set('monthly_price', '1')
        ->set('features', ['inventada'])
        ->call('save')
        ->assertHasErrors('features.*');
});

test('a plan with subscriptions cannot be deleted', function () {
    [, $establishment] = catalogFixture();
    $plan = $establishment->currentPlan();

    Livewire::actingAs(platformAdmin())->test('pages::admin.plans')->call('delete', $plan->id);

    expect(Plan::whereKey($plan->id)->exists())->toBeTrue();
});

test('subscriptions suggest the end date from the plan duration', function () {
    [, $establishment] = catalogFixture();
    $plan = Plan::create(['name' => 'Corto', 'monthly_price' => 1, 'duration_days' => 10, 'status' => 'active']);

    Livewire::actingAs(platformAdmin())
        ->test('pages::admin.subscriptions')
        ->call('create')
        ->set('establishment_id', (string) $establishment->id)
        ->set('start_date', '2026-01-01')
        ->set('plan_id', (string) $plan->id)
        ->assertSet('end_date', '2026-01-11')
        ->call('save')
        ->assertHasNoErrors();

    expect(Subscription::where('plan_id', $plan->id)->exists())->toBeTrue();
});

test('an expired subscription stops the orders', function () {
    [, $establishment] = catalogFixture();
    $establishment->subscriptions()->update(['end_date' => now()->subDay()]);

    expect($establishment->fresh()->canReceiveOrders())->toBeFalse();
});

test('inactive accounts cannot sign in', function () {
    $user = User::factory()->create(['status' => 'inactive']);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

test('an account deactivated during its session loses access', function () {
    [$owner] = catalogFixture();
    $admin = User::factory()->create(['role' => 'platform_admin', 'status' => 'inactive']);
    $owner->forceFill(['status' => 'inactive'])->save();

    $this->actingAs($owner)->get(route('products.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertForbidden();
});

test('role and status cannot be mass assigned', function () {
    $user = new User(['role' => 'platform_admin', 'status' => 'inactive']);

    expect($user->role)->toBeNull()->and($user->status)->toBeNull();
});
