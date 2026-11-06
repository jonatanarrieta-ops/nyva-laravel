<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\Cart;

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

    $this->post(route('cart.items.store', $product))
        ->assertRedirect(route('catalog.show', $establishment))
        ->assertSessionHas('error', "{$product->name} no está disponible.");

    expect(app(Cart::class)->count())->toBe(0);
});

test('inactive products and products of private businesses cannot be added', function () {
    [, $establishment, , $product] = catalogFixture();
    [, , , $hidden] = catalogFixture(['status' => 'inactive']);

    $establishment->forceFill(['status' => 'inactive'])->save();

    $this->post(route('cart.items.store', $product))->assertNotFound();
    $this->post(route('cart.items.store', $hidden))->assertNotFound();

    expect(app(Cart::class)->count())->toBe(0);
});

test('the cart adds, changes quantities and calculates totals', function () {
    [, $establishment, $category, $product] = catalogFixture();
    $soda = Product::create(['category_id' => $category->id, 'name' => 'Gaseosa', 'price' => 5000, 'status' => 'active', 'available' => true]);

    $this->post(route('cart.items.store', $product))->assertRedirect(route('catalog.show', $establishment));
    $this->post(route('cart.items.store', $product));
    $this->post(route('cart.items.store', $soda))->assertSessionHas('notice');

    $cart = app(Cart::class);

    expect($cart->establishmentId())->toBe($establishment->id)
        ->and($cart->count())->toBe(3)
        ->and($cart->lines()->sum('subtotal'))->toBe(55000.0);

    $this->get(route('cart.index'))->assertOk()->assertSee('$ 55.000');

    $this->patch(route('cart.items.update', $soda->id), ['quantity' => 2])->assertRedirect(route('cart.index'));
    $this->get(route('cart.index'))->assertSee('$ 60.000');

    $this->patch(route('cart.items.update', $product->id), ['quantity' => 1]);
    $this->get(route('cart.index'))->assertSee('$ 35.000');

    $this->delete(route('cart.items.destroy', $soda->id))->assertRedirect(route('cart.index'));
    $this->get(route('cart.index'))->assertSee('$ 25.000');

    $this->delete(route('cart.clear'))->assertRedirect(route('cart.index'));
    $this->get(route('cart.index'))->assertSee('Tu carrito está vacío.');
});

test('cart quantities are validated on the server', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);

    $this->patch(route('cart.items.update', $product->id), ['quantity' => 500])->assertSessionHasErrors('quantity');
    $this->patch(route('cart.items.update', $product->id), ['quantity' => 'dos'])->assertSessionHasErrors('quantity');

    expect(app(Cart::class)->quantities())->toBe([$product->id => 1]);
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

    $this->post(route('cart.items.store', $otherProduct))
        ->assertRedirect(route('catalog.show', $other))
        ->assertSessionHas('pending_product_id', $otherProduct->id);

    $this->get(route('catalog.show', $other))->assertSee('Tu carrito contiene productos de otro establecimiento.');

    expect(array_keys($cart->quantities()))->toBe([$product->id]);

    $this->post(route('cart.items.store', $otherProduct), ['replace' => 1]);

    expect(array_keys($cart->quantities()))->toBe([$otherProduct->id])
        ->and($cart->establishmentId())->toBe($other->id);
});

test('keeping the cart discards the pending product', function () {
    [, , , $product] = catalogFixture();
    [, $other, , $otherProduct] = catalogFixture();
    $cart = app(Cart::class);
    $cart->add($product);

    $this->post(route('cart.items.store', $otherProduct));
    $this->get(route('catalog.show', $other))->assertSee('Tu carrito contiene productos de otro establecimiento.');
    $this->get(route('catalog.show', $other))->assertDontSee('Tu carrito contiene productos de otro establecimiento.');

    expect(array_keys($cart->quantities()))->toBe([$product->id]);
});

test('a product that stopped being available blocks the checkout link', function () {
    [, , , $product] = catalogFixture();
    app(Cart::class)->add($product);
    $product->update(['available' => false]);

    $this->get(route('cart.index'))
        ->assertSee('Algunos productos ya no están disponibles.')
        ->assertDontSee(route('checkout'));
});

test('inactive categories are hidden from the catalog', function () {
    [, $establishment, $category] = catalogFixture();
    Category::whereKey($category->id)->update(['status' => 'inactive']);

    $this->get(route('catalog.show', $establishment))
        ->assertDontSee($category->name);
});

test('public pages do not link to accounts or panels', function (bool $signedIn) {
    [$owner, $establishment] = catalogFixture();

    if ($signedIn) {
        $this->actingAs($owner);
    }

    $this->get(route('catalog.show', $establishment))
        ->assertOk()
        ->assertDontSee(route('login'), false)
        ->assertDontSee(route('register'), false)
        ->assertDontSee(route('dashboard'), false)
        ->assertDontSee(route('my-orders.index'), false)
        ->assertDontSee(route('logout'), false);
})->with(['visitante' => false, 'con sesión' => true]);
