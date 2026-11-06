<?php

use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

function platformAdmin(): User
{
    return User::factory()->create(['role' => 'platform_admin']);
}

test('only platform admins can open the platform panel', function (string $routeName) {
    [$owner] = catalogFixture();

    $this->actingAs($owner)->get(route($routeName))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route($routeName))->assertForbidden();
    $this->actingAs(platformAdmin())->get(route($routeName))->assertOk();
})->with(['admin.dashboard', 'admin.establishments.index', 'admin.establishments.create', 'admin.users.index', 'admin.users.create', 'admin.plans.index', 'admin.plans.create', 'admin.subscriptions.index', 'admin.subscriptions.create', 'admin.statistics']);

test('platform admins are sent to their dashboard and cannot use a business panel', function () {
    $admin = platformAdmin();

    $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    $this->actingAs($admin)->get(route('products.index'))->assertForbidden();
});

test('the platform registers an establishment with a new owner', function () {
    $this->actingAs(platformAdmin())
        ->post(route('admin.establishments.store'), [
            'name' => 'Cocina Sur',
            'type' => 'Cocina oculta',
            'address' => 'Calle 1',
            'phone' => '3001234567',
            'owner_mode' => 'new',
            'owner_first_name' => 'Ana',
            'owner_last_name' => 'Ruiz',
            'owner_email' => 'ana@example.com',
            'owner_password' => 'password123',
        ])
        ->assertRedirect(route('admin.establishments.index'))
        ->assertSessionHasNoErrors();

    $establishment = Establishment::firstOrFail();

    expect($establishment->slug)->toBe('cocina-sur')
        ->and($establishment->status)->toBe('active')
        ->and($establishment->user->role)->toBe('establishment_admin')
        ->and($establishment->user->email)->toBe('ana@example.com');

    $this->get(route('admin.establishments.edit', $establishment))->assertOk()->assertSee('Cocina Sur');

    $this->put(route('admin.establishments.update', $establishment), [
        'name' => 'Cocina Sur Centro',
        'type' => 'Cocina oculta',
        'address' => 'Calle 2',
        'phone' => '3007654321',
        'owner_mode' => 'existing',
        'owner_id' => $establishment->user_id,
    ])->assertSessionHasNoErrors();

    expect($establishment->fresh()->name)->toBe('Cocina Sur Centro')
        ->and(User::where('role', 'establishment_admin')->count())->toBe(1);
});

test('establishment form rejects invalid data', function () {
    $this->actingAs(platformAdmin())
        ->post(route('admin.establishments.store'), ['phone' => 'abc', 'owner_mode' => 'new'])
        ->assertSessionHasErrors(['name', 'type', 'address', 'phone']);

    $this->post(route('admin.establishments.store'), [
        'name' => 'Sin dueño',
        'type' => 'Hotel',
        'address' => 'Calle 1',
        'phone' => '3001234567',
        'owner_mode' => 'existing',
        'owner_id' => User::factory()->create()->id,
    ])->assertSessionHasErrors('owner_id');

    expect(Establishment::count())->toBe(0);
});

test('suspending an establishment blocks it and alerts the owner', function () {
    [$owner, $establishment] = catalogFixture();

    $this->actingAs(platformAdmin())
        ->patch(route('admin.establishments.status', $establishment), ['status' => 'suspended'])
        ->assertSessionHas('success');

    expect($establishment->fresh()->canReceiveOrders())->toBeFalse()
        ->and($owner->notifications()->count())->toBe(1);

    $this->patch(route('admin.establishments.status', $establishment), ['status' => 'cerrado'])
        ->assertSessionHasErrors('status');

    Auth::logout();

    $this->get(route('catalog.show', $establishment))->assertNotFound();
});

test('users are managed by the platform and cannot lock out the current admin', function () {
    $admin = platformAdmin();
    $customer = User::factory()->create();

    $this->actingAs($admin);

    $this->patch(route('admin.users.status', $customer))->assertSessionHas('success');
    $this->patch(route('admin.users.status', $admin))->assertSessionHas('error');

    expect($customer->fresh()->status)->toBe('inactive')
        ->and($admin->fresh()->status)->toBe('active');

    $this->put(route('admin.users.update', $admin), [
        'first_name' => $admin->first_name,
        'last_name' => $admin->last_name,
        'email' => $admin->email,
        'role' => 'customer',
    ])->assertSessionHasErrors('role');

    expect($admin->fresh()->isPlatformAdmin())->toBeTrue();

    $this->post(route('admin.users.store'), [
        'first_name' => 'Luis',
        'last_name' => 'Pérez',
        'email' => 'luis@example.com',
        'password' => 'password123',
        'role' => 'establishment_admin',
    ])->assertRedirect(route('admin.users.index'));

    $luis = User::where('email', 'luis@example.com')->firstOrFail();

    expect($luis->role)->toBe('establishment_admin')
        ->and($luis->isActive())->toBeTrue();

    $this->put(route('admin.users.update', $luis), [
        'first_name' => 'Luis Carlos',
        'last_name' => 'Pérez',
        'email' => 'luis@example.com',
        'password' => '',
        'role' => 'establishment_admin',
    ])->assertSessionHasNoErrors();

    expect($luis->fresh()->first_name)->toBe('Luis Carlos')
        ->and($luis->fresh()->password)->toBe($luis->password);
});

test('invalid roles are rejected', function () {
    $this->actingAs(platformAdmin())
        ->post(route('admin.users.store'), [
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'a@example.com',
            'password' => 'password123',
            'role' => 'superuser',
        ])
        ->assertSessionHasErrors('role');

    expect(User::where('email', 'a@example.com')->exists())->toBeFalse();
});

test('plans store price, duration, limits and features', function () {
    $this->actingAs(platformAdmin())
        ->post(route('admin.plans.store'), [
            'name' => 'Inicial',
            'monthly_price' => '30000',
            'duration_days' => '60',
            'product_limit' => '10',
            'user_limit' => '2',
            'features' => ['chatbot', 'public_order'],
            'status' => '1',
        ])
        ->assertRedirect(route('admin.plans.index'))
        ->assertSessionHasNoErrors();

    $plan = Plan::firstOrFail();

    expect($plan->duration_days)->toBe(60)
        ->and($plan->product_limit)->toBe(10)
        ->and($plan->user_limit)->toBe(2)
        ->and($plan->status)->toBe('active')
        ->and($plan->hasFeature('chatbot'))->toBeTrue()
        ->and($plan->hasFeature('reports'))->toBeFalse();

    $this->get(route('admin.plans.edit', $plan))->assertOk()->assertSee('Inicial');

    $this->put(route('admin.plans.update', $plan), [
        'name' => 'Inicial plus',
        'monthly_price' => '35000',
        'duration_days' => '30',
        'product_limit' => '',
        'features' => [],
        'status' => '0',
    ])->assertSessionHasNoErrors();

    $plan->refresh();

    expect($plan->name)->toBe('Inicial plus')
        ->and($plan->product_limit)->toBeNull()
        ->and($plan->features)->toBe([])
        ->and($plan->status)->toBe('inactive');
});

test('unknown plan features are rejected', function () {
    $this->actingAs(platformAdmin())
        ->post(route('admin.plans.store'), [
            'name' => 'Mal',
            'monthly_price' => '1',
            'duration_days' => '30',
            'features' => ['inventada'],
        ])
        ->assertSessionHasErrors('features.0');

    expect(Plan::where('name', 'Mal')->exists())->toBeFalse();
});

test('a plan with subscriptions cannot be deleted', function () {
    [, $establishment] = catalogFixture();
    $plan = $establishment->currentPlan();
    $free = Plan::create(['name' => 'Libre', 'monthly_price' => 0, 'duration_days' => 30, 'status' => 'active']);

    $this->actingAs(platformAdmin())
        ->delete(route('admin.plans.destroy', $plan))
        ->assertSessionHas('error');

    $this->delete(route('admin.plans.destroy', $free))->assertSessionHas('success');

    expect(Plan::whereKey($plan->id)->exists())->toBeTrue()
        ->and(Plan::whereKey($free->id)->exists())->toBeFalse();
});

test('subscriptions suggest the end date from the plan duration', function () {
    [, $establishment] = catalogFixture();
    $plan = Plan::create(['name' => 'Corto', 'monthly_price' => 1, 'duration_days' => 10, 'status' => 'active']);

    $this->actingAs(platformAdmin())
        ->post(route('admin.subscriptions.store'), [
            'establishment_id' => $establishment->id,
            'plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'start_date' => '2026-01-01',
            'end_date' => '',
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.subscriptions.index'))
        ->assertSessionHasNoErrors();

    $subscription = Subscription::where('plan_id', $plan->id)->firstOrFail();

    expect($subscription->end_date->toDateString())->toBe('2026-01-11');

    $this->get(route('admin.subscriptions.edit', $subscription))->assertOk()->assertSee('2026-01-11');

    $this->put(route('admin.subscriptions.update', $subscription), [
        'establishment_id' => $establishment->id,
        'plan_id' => $plan->id,
        'billing_period' => 'annual',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'status' => 'cancelled',
    ])->assertSessionHasNoErrors();

    expect($subscription->fresh()->status)->toBe('cancelled')
        ->and($subscription->fresh()->end_date->toDateString())->toBe('2026-12-31');

    $this->put(route('admin.subscriptions.update', $subscription), [
        'establishment_id' => $establishment->id,
        'plan_id' => $plan->id,
        'billing_period' => 'monthly',
        'start_date' => '2026-05-01',
        'end_date' => '2026-01-01',
        'status' => 'active',
    ])->assertSessionHasErrors('end_date');
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
