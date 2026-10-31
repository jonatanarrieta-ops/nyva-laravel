<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Crea un administrador con un establecimiento, una categoría y un producto.
 *
 * @return array{0: App\Models\User, 1: App\Models\Establishment, 2: App\Models\Category, 3: App\Models\Product}
 */
function catalogFixture(array $productAttributes = []): array
{
    $admin = App\Models\User::factory()->create(['role' => 'establishment_admin']);

    $establishment = App\Models\Establishment::create([
        'user_id' => $admin->id,
        'name' => 'Local de prueba',
        'type' => 'restaurant',
        'description' => 'Descripción de prueba',
        'address' => 'Calle 1 # 2-3',
        'phone' => '3001234567',
        'email' => 'local@example.com',
        'status' => 'active',
    ]);

    $category = App\Models\Category::create([
        'establishment_id' => $establishment->id,
        'name' => 'Hamburguesas',
        'status' => 'active',
    ]);

    $product = App\Models\Product::create([
        'category_id' => $category->id,
        'name' => 'Hamburguesa clásica',
        'price' => 25000,
        'available' => true,
        'status' => 'active',
        ...$productAttributes,
    ]);

    return [$admin, $establishment, $category, $product];
}
