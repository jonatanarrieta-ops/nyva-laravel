<?php

use App\Models\User;

test('customers are redirected away from the dashboard', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertRedirect(route('home'));
});

test('customers cannot open administrative modules', function (string $routeName) {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route($routeName))->assertForbidden();
})->with(['establishments.index', 'categories.index', 'products.index', 'orders.index', 'reports.index']);

test('guests are redirected to login from administrative modules', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['establishments.index', 'categories.index', 'products.index', 'orders.index', 'reports.index']);

test('admins can open administrative modules', function (string $routeName) {
    [$admin] = catalogFixture();

    $this->actingAs($admin)->get(route($routeName))->assertOk();
})->with(['establishments.index', 'categories.index', 'products.index', 'orders.index', 'reports.index']);

test('registered users get the customer role', function () {
    $this->post(route('register.store'), [
        'first_name' => 'Ana',
        'last_name' => 'Ruiz',
        'email' => 'ana@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'ana@example.com')->firstOrFail();

    expect($user->role)->toBe('customer')
        ->and($user->isAdmin())->toBeFalse();
});
