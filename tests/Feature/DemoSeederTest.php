<?php

use App\Models\Establishment;
use Database\Seeders\DemoSeeder;

test('each demo business has its own owner and isolated data', function () {
    $this->seed(DemoSeeder::class);

    $burger = Establishment::where('slug', 'hamburguesas-la-esquina')->firstOrFail();
    $cafe = Establishment::where('slug', 'cafe-aurora')->firstOrFail();

    expect($burger->user->email)->toBe('demo.admin@nyva.test')
        ->and($cafe->user->email)->toBe('demo.cafe@nyva.test')
        ->and($burger->user->accessibleEstablishments()->pluck('id')->all())->toBe([$burger->id])
        ->and($cafe->user->accessibleEstablishments()->pluck('id')->all())->toBe([$cafe->id]);

    $this->actingAs($cafe->user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertDontSee('Hamburguesa clásica');
});

test('the demo seeder can run again without mixing owners', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Establishment::where('slug', 'cafe-aurora')->firstOrFail()->user->email)->toBe('demo.cafe@nyva.test')
        ->and(Establishment::count())->toBe(2);
});
