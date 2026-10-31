<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('admins can visit the dashboard', function () {
    $user = User::factory()->create(['role' => 'establishment_admin']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
