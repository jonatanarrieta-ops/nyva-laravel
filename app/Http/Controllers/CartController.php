<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        $lines = $cart->lines();
        $establishment = Establishment::find($cart->establishmentId());

        return view('public.cart.index', [
            'title' => 'Carrito',
            'publicEstablishment' => $establishment?->isPublic() ? $establishment : null,
            'establishment' => $establishment,
            'lines' => $lines,
            'total' => $lines->sum('subtotal'),
            'hasUnavailable' => $lines->contains('orderable', false),
        ]);
    }

    public function store(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $product->loadMissing('category.establishment');
        $establishment = $product->category?->establishment;

        abort_unless(
            $product->status === 'active'
                && $product->category?->status === 'active'
                && $establishment?->isPublic(),
            404,
        );

        if ($request->boolean('replace')) {
            $cart->clear();
        }

        $back = redirect()->route('catalog.show', $establishment);

        return match ($cart->add($product)) {
            Cart::CONFLICT => $back->with('pending_product_id', $product->id),
            Cart::UNAVAILABLE => $back->with('error', "{$product->name} no está disponible."),
            Cart::CLOSED => $back->with('error', $establishment->orderBlockReason() ?? 'No es posible recibir pedidos.'),
            default => $back->with('notice', "{$product->name} se agregó al carrito."),
        };
    }

    public function update(Request $request, int $product, Cart $cart): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.PlaceOrder::MAX_QUANTITY],
        ]);

        $cart->setQuantity($product, (int) $data['quantity']);

        return redirect()->route('cart.index');
    }

    public function destroy(int $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product);

        return redirect()->route('cart.index');
    }

    public function clear(Cart $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('cart.index');
    }
}
