<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function productData(int $categoryId, array $overrides = []): array
{
    return [
        'category_id' => $categoryId,
        'name' => 'Gaseosa',
        'price' => '5000',
        'available' => '1',
        'status' => '1',
        ...$overrides,
    ];
}

test('the catalog routes follow the resource convention', function () {
    [$admin, , $category, $product] = catalogFixture();

    $this->actingAs($admin);

    $this->get(route('categories.index'))->assertOk()->assertSee($category->name);
    $this->get(route('categories.create'))->assertOk()->assertSee('Nueva categoría');
    $this->get(route('categories.edit', $category->id))->assertOk()->assertSee($category->name);
    $this->get(route('products.index'))->assertOk()->assertSee($product->name);
    $this->get(route('products.create'))->assertOk()->assertSee('Nuevo producto');
    $this->get(route('products.edit', $product->id))->assertOk()->assertSee($product->name);
});

test('product status is stored as text and restored in the form', function () {
    [$admin, , $category] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('products.store'), productData($category->id, ['status' => '0']))
        ->assertRedirect(route('products.index'))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Gaseosa')->firstOrFail();

    expect($product->status)->toBe('inactive');

    $this->get(route('products.edit', $product->id))
        ->assertOk()
        ->assertDontSee('name="status" value="1" checked', false);
});

test('a product is updated and deleted through its resource routes', function () {
    [$admin, , $category, $product] = catalogFixture();

    $this->actingAs($admin)
        ->put(route('products.update', $product->id), productData($category->id, ['name' => 'Editado', 'price' => '30000']))
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('success');

    expect($product->fresh()->name)->toBe('Editado')
        ->and((float) $product->fresh()->price)->toBe(30000.0);

    $this->delete(route('products.destroy', $product->id))->assertRedirect(route('products.index'));

    expect(Product::whereKey($product->id)->exists())->toBeFalse();
});

test('a product with orders cannot be deleted', function () {
    [$admin, $establishment, , $product] = catalogFixture();
    $order = makeOrder($establishment->id);
    $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 25000, 'subtotal' => 25000]);

    $this->actingAs($admin)
        ->delete(route('products.destroy', $product->id))
        ->assertSessionHas('error');

    expect(Product::whereKey($product->id)->exists())->toBeTrue();
});

test('a valid product image is stored on the public disk', function () {
    Storage::fake('public');
    [$admin, , $category, $product] = catalogFixture();

    $this->actingAs($admin)
        ->put(route('products.update', $product->id), productData($category->id, [
            'image' => UploadedFile::fake()->create('foto.png', 100, 'image/png'),
        ]))
        ->assertSessionHasNoErrors();

    $image = $product->fresh()->image;

    expect($image)->toStartWith('products/');
    Storage::disk('public')->assertExists($image);

    $this->put(route('products.update', $product->id), productData($category->id, ['remove_image' => '1']));

    expect($product->fresh()->image)->toBeNull();
    Storage::disk('public')->assertMissing($image);
});

test('dangerous or oversized files are rejected as product images', function (string $name, int $kilobytes, string $mime) {
    Storage::fake('public');
    [$admin, , $category, $product] = catalogFixture();

    $this->actingAs($admin)
        ->put(route('products.update', $product->id), productData($category->id, [
            'image' => UploadedFile::fake()->create($name, $kilobytes, $mime),
        ]))
        ->assertSessionHasErrors('image');

    expect($product->fresh()->image)->toBeNull();
})->with([
    ['shell.php', 10, 'application/x-php'],
    ['vector.svg', 10, 'image/svg+xml'],
    ['enorme.png', 3000, 'image/png'],
]);

test('product validation rejects invalid data', function () {
    [$admin, , $category] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('products.store'), productData($category->id, ['name' => '', 'price' => '-1']))
        ->assertSessionHasErrors(['name', 'price']);
});

test('a product cannot use a category from another owner', function () {
    [$admin] = catalogFixture();
    [, , $foreignCategory] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('products.store'), productData($foreignCategory->id, ['name' => 'Intruso']))
        ->assertSessionHasErrors('category_id');

    expect(Product::where('name', 'Intruso')->exists())->toBeFalse();
});

test('category status is stored as text', function () {
    [$admin, $establishment] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('categories.store'), [
            'establishment_id' => $establishment->id,
            'name' => 'Bebidas',
            'status' => '0',
        ])
        ->assertRedirect(route('categories.index'))
        ->assertSessionHasNoErrors();

    expect(Category::where('name', 'Bebidas')->value('status'))->toBe('inactive');
});

test('a category is updated through its resource route', function () {
    [$admin, $establishment, $category] = catalogFixture();

    $this->actingAs($admin)
        ->put(route('categories.update', $category->id), [
            'establishment_id' => $establishment->id,
            'name' => 'Combos',
            'status' => '1',
        ])
        ->assertRedirect(route('categories.index'));

    expect($category->fresh()->name)->toBe('Combos');
});

test('a category with products cannot be deleted and an empty one can', function () {
    [$admin, $establishment, $category] = catalogFixture();
    $empty = Category::create(['establishment_id' => $establishment->id, 'name' => 'Vacía', 'status' => 'active']);

    $this->actingAs($admin)
        ->delete(route('categories.destroy', $category->id))
        ->assertSessionHas('error');

    $this->delete(route('categories.destroy', $empty->id))->assertSessionHas('success');

    expect(Category::whereKey($category->id)->exists())->toBeTrue()
        ->and(Category::whereKey($empty->id)->exists())->toBeFalse();
});

test('a category cannot use an establishment from another owner', function () {
    [$admin] = catalogFixture();
    [, $foreign] = catalogFixture();

    $this->actingAs($admin)
        ->post(route('categories.store'), ['establishment_id' => $foreign->id, 'name' => 'Intrusa'])
        ->assertSessionHasErrors('establishment_id');

    expect(Category::where('name', 'Intrusa')->exists())->toBeFalse();
});

test('another owner cannot edit or delete foreign categories and products', function () {
    [, $establishment, $category, $product] = catalogFixture();
    $other = User::factory()->create(['role' => 'establishment_admin']);
    $other->establishments()->create([
        'name' => 'Otro local',
        'type' => 'restaurant',
        'address' => 'Calle 9',
        'phone' => '3000000000',
    ]);

    $this->actingAs($other);

    $this->get(route('categories.edit', $category->id))->assertNotFound();
    $this->put(route('categories.update', $category->id), ['establishment_id' => $establishment->id, 'name' => 'X'])->assertNotFound();
    $this->delete(route('categories.destroy', $category->id))->assertNotFound();
    $this->get(route('products.edit', $product->id))->assertNotFound();
    $this->delete(route('products.destroy', $product->id))->assertNotFound();

    expect(Product::whereKey($product->id)->exists())->toBeTrue()
        ->and($category->fresh()->name)->not->toBe('X');
});
