<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Services\Cart;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function create(Cart $cart): View|RedirectResponse
    {
        if ($cart->quantities() === []) {
            return redirect()->route('cart.index');
        }

        $lines = $cart->lines();
        $establishment = Establishment::find($cart->establishmentId());
        $user = Auth::user();

        return view('public.checkout.create', [
            'title' => 'Confirmar pedido',
            'publicEstablishment' => $establishment?->isPublic() ? $establishment : null,
            'establishment' => $establishment,
            'lines' => $lines,
            'total' => $lines->sum('subtotal'),
            'orderKey' => (string) Str::uuid(),
            'customerName' => $user ? trim($user->first_name.' '.$user->last_name) : '',
        ]);
    }

    public function store(Request $request, Cart $cart, PlaceOrder $placeOrder): RedirectResponse
    {
        if ($cart->quantities() === []) {
            return redirect()->route('cart.index');
        }

        $request->validate([
            'order_key' => ['required', 'uuid'],
        ]);

        $order = $placeOrder->handle(
            (int) $cart->establishmentId(),
            $cart->quantities(),
            [
                'customer_name' => trim((string) $request->input('customer_name')),
                'customer_phone' => trim((string) $request->input('customer_phone')),
                'delivery_address' => trim((string) $request->input('delivery_address')),
            ],
            Auth::id(),
            'web',
            $request->input('order_key'),
        );

        $cart->clear();

        return Auth::check()
            ? redirect()->route('my-orders.confirmation', $order->id)
            : redirect()->route('orders.track', $order->public_token);
    }
}
