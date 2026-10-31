<?php

use App\Models\Establishment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('an admin without establishments sees the empty page', function () {
    $admin = User::factory()->create(['role' => 'establishment_admin']);

    $this->actingAs($admin)
        ->get(route('establishments.index'))
        ->assertOk()
        ->assertSee('Aún no tienes establecimientos');
});

test('an admin can create and edit an establishment', function () {
    $admin = User::factory()->create(['role' => 'establishment_admin']);

    Livewire::actingAs($admin)
        ->test('pages::establishments.index')
        ->call('createEstablishment')
        ->set('name', 'Café Central')
        ->set('type', 'Cafetería')
        ->set('address', 'Carrera 5 # 10-20')
        ->set('phone', '300 123 4567')
        ->call('save')
        ->assertHasNoErrors();

    $establishment = $admin->establishments()->firstOrFail();

    expect($establishment->status)->toBe('active')
        ->and($establishment->registered_at)->not->toBeNull();

    Livewire::actingAs($admin)
        ->test('pages::establishments.index')
        ->call('editEstablishment', $establishment->id)
        ->set('name', 'Café del Centro')
        ->set('status', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($establishment->fresh())
        ->name->toBe('Café del Centro')
        ->status->toBe('inactive');
});

test('establishment validation rejects invalid data', function () {
    $admin = User::factory()->create(['role' => 'establishment_admin']);

    Livewire::actingAs($admin)
        ->test('pages::establishments.index')
        ->call('createEstablishment')
        ->set('phone', 'abc')
        ->set('email', 'no-es-correo')
        ->call('save')
        ->assertHasErrors(['name', 'type', 'address', 'phone', 'email']);

    expect(Establishment::count())->toBe(0);
});

test('an establishment with orders or categories cannot be deleted', function () {
    [$admin, $establishment] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('pages::establishments.index')
        ->call('deleteEstablishment', $establishment->id);

    expect(Establishment::whereKey($establishment->id)->exists())->toBeTrue();
});

test('foreign establishments cannot be edited nor deleted', function () {
    [, $establishment] = catalogFixture();
    $other = User::factory()->create(['role' => 'establishment_admin']);

    foreach (['editEstablishment', 'deleteEstablishment'] as $method) {
        expect(fn () => Livewire::actingAs($other)
            ->test('pages::establishments.index')
            ->call($method, $establishment->id))
            ->toThrow(ModelNotFoundException::class);
    }
});
