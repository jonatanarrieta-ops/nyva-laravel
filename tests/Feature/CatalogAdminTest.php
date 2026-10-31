<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('product status is stored as text and restored in the form', function () {
    [$admin, , $category] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('pages::products.index')
        ->call('createProduct')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Gaseosa')
        ->set('price', '5000')
        ->set('status', false)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Gaseosa')->firstOrFail();

    expect($product->status)->toBe('inactive');

    Livewire::actingAs($admin)
        ->test('pages::products.index')
        ->call('editProduct', $product->id)
        ->assertSet('status', false);
});

test('product validation rejects invalid data', function () {
    [$admin, , $category] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('pages::products.index')
        ->call('createProduct')
        ->set('categoryId', (string) $category->id)
        ->set('name', '')
        ->set('price', '-1')
        ->call('save')
        ->assertHasErrors(['name', 'price']);
});

test('a product cannot use a category from another owner', function () {
    [$admin] = catalogFixture();
    [, , $foreignCategory] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('pages::products.index')
        ->call('createProduct')
        ->set('categoryId', (string) $foreignCategory->id)
        ->set('name', 'Intruso')
        ->set('price', '1000')
        ->call('save')
        ->assertHasErrors('categoryId');

    expect(Product::where('name', 'Intruso')->exists())->toBeFalse();
});

test('category status is stored as text', function () {
    [$admin, $establishment] = catalogFixture();

    Livewire::actingAs($admin)
        ->test('pages::categories.index')
        ->call('createCategory')
        ->set('establishmentId', (string) $establishment->id)
        ->set('name', 'Bebidas')
        ->set('status', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('name', 'Bebidas')->value('status'))->toBe('inactive');
});

test('another owner cannot edit or delete foreign categories and products', function () {
    [, , $category, $product] = catalogFixture();
    $other = User::factory()->create(['role' => 'establishment_admin']);

    expect(fn () => Livewire::actingAs($other)
        ->test('pages::categories.index')
        ->call('editCategory', $category->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => Livewire::actingAs($other)
        ->test('pages::products.index')
        ->call('deleteProduct', $product->id))
        ->toThrow(ModelNotFoundException::class);

    expect(Product::whereKey($product->id)->exists())->toBeTrue();
});
