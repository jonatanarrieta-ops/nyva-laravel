<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('the home page never lists the establishments', function () {
    [, $establishment] = catalogFixture();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee($establishment->name)
        ->assertDontSee($establishment->slug);
});

test('a business page only links to that business', function () {
    [, $establishment] = catalogFixture();
    [, $other] = catalogFixture();
    $other->update(['name' => 'Otro negocio']);

    $this->get(route('catalog.show', $establishment))
        ->assertOk()
        ->assertSee(route('catalog.show', $establishment), false)
        ->assertDontSee('Otro negocio')
        ->assertDontSee($other->slug)
        ->assertDontSee('>Catálogo</a>', false);
});

test('the cart and the tracking page show the business of the order', function () {
    [, $establishment, , $product] = catalogFixture();
    app(Cart::class)->add($product);
    $order = makeOrder($establishment->id);

    $this->get(route('cart.index'))->assertSee(route('catalog.show', $establishment), false);
    $this->get(route('orders.track', $order->public_token))->assertSee(route('catalog.show', $establishment), false);
});

test('the public catalog shows the establishment organized by category', function () {
    [, $establishment, $category, $product] = catalogFixture();

    $this->get(route('catalog.show', $establishment))
        ->assertOk()
        ->assertSee($establishment->name)
        ->assertSee($establishment->address)
        ->assertSee($establishment->phone)
        ->assertSee($category->name)
        ->assertSee($product->name)
        ->assertSee('$ 25.000');
});

test('inactive and missing establishments are not public', function () {
    [, $establishment] = catalogFixture();
    $establishment->forceFill(['status' => 'inactive'])->save();

    $this->get(route('catalog.show', $establishment))->assertNotFound();
    $this->get(route('catalog.show', 'no-existe'))->assertNotFound();
});

test('inactive products are hidden and unavailable ones are labelled', function () {
    [, $establishment, $category] = catalogFixture();
    Product::create(['category_id' => $category->id, 'name' => 'Producto oculto', 'price' => 10, 'status' => 'inactive', 'available' => true]);
    Product::create(['category_id' => $category->id, 'name' => 'Producto agotado', 'price' => 10, 'status' => 'active', 'available' => false]);

    $this->get(route('catalog.show', $establishment))
        ->assertDontSee('Producto oculto')
        ->assertSee('Producto agotado')
        ->assertSee('No disponible');
});

test('an unavailable product cannot be added to the cart', function () {
    [, $establishment, , $product] = catalogFixture(['available' => false]);

    Livewire::test('pages::catalog.show', ['establishment' => $establishment])
        ->call('addToCart', $product->id)
        ->assertSet('error', "{$product->name} no está disponible.");

    expect(app(Cart::class)->count())->toBe(0);
});

test('a product from another establishment cannot be added from this catalog', function () {
    [, $establishment] = catalogFixture();
    [, , , $foreignProduct] = catalogFixture();

    expect(fn () => Livewire::test('pages::catalog.show', ['establishment' => $establishment])
        ->call('addToCart', $foreignProduct->id))
        ->toThrow(ModelNotFoundException::class);
});

test('the cart adds, changes quantities and calculates totals', function () {
    [, $establishment, $category, $product] = catalogFixture();
    $soda = Product::create(['category_id' => $category->id, 'name' => 'Gaseosa', 'price' => 5000, 'status' => 'active', 'available' => true]);

    Livewire::test('pages::catalog.show', ['establishment' => $establishment])
        ->call('addToCart', $product->id)
        ->call('addToCart', $product->id)
        ->call('addToCart', $soda->id)
        ->assertDispatched('cart-updated');

    $cart = app(Cart::class);

    expect($cart->establishmentId())->toBe($establishment->id)
        ->and($cart->count())->toBe(3)
        ->and($cart->lines()->sum('subtotal'))->toBe(55000.0);

    Livewire::test('pages::cart.index')
        ->assertSee('$ 55.000')
        ->call('increase', $soda->id)
        ->assertSee('$ 60.000')
        ->call('decrease', $product->id)
        ->assertSee('$ 35.000')
        ->call('remove', $soda->id)
        ->assertSee('$ 25.000')
        ->call('clear')
        ->assertSee('Tu carrito está vacío.');
});

test('the cart uses the current database price', function () {
    [, , , $product] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    $product->update(['price' => 30000]);

    expect($cart->lines()->sum('subtotal'))->toBe(30000.0);
});

test('decreasing the last unit removes the product', function () {
    [, , , $product] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    $cart->decrease($product->id);

    expect($cart->quantities())->toBe([])
        ->and($cart->establishmentId())->toBeNull();
});

test('adding from another establishment warns and never mixes products', function () {
    [, , , $product] = catalogFixture();
    [, $other, , $otherProduct] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    $component = Livewire::test('pages::catalog.show', ['establishment' => $other])
        ->call('addToCart', $otherProduct->id)
        ->assertSet('pendingProductId', $otherProduct->id)
        ->assertSee('Tu carrito contiene productos de otro establecimiento.');

    expect(array_keys($cart->quantities()))->toBe([$product->id]);

    $component->call('replaceCart')->assertSet('pendingProductId', null);

    expect(array_keys($cart->quantities()))->toBe([$otherProduct->id])
        ->and($cart->establishmentId())->toBe($other->id);
});

test('keeping the cart discards the pending product', function () {
    [, , , $product] = catalogFixture();
    [, $other, , $otherProduct] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    Livewire::test('pages::catalog.show', ['establishment' => $other])
        ->call('addToCart', $otherProduct->id)
        ->call('keepCart')
        ->assertSet('pendingProductId', null);

    expect(array_keys($cart->quantities()))->toBe([$product->id]);
});

test('a product that stopped being available blocks the checkout link', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);
    $product->update(['available' => false]);

    Livewire::test('pages::cart.index')
        ->assertSee('Algunos productos ya no están disponibles.')
        ->assertDontSee(route('checkout'));
});

test('inactive categories are hidden from the catalog', function () {
    [, $establishment, $category] = catalogFixture();
    Category::whereKey($category->id)->update(['status' => 'inactive']);

    $this->get(route('catalog.show', $establishment))
        ->assertDontSee($category->name);
});
