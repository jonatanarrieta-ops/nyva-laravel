<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    public function show(string $token): View
    {
        $order = Order::query()
            ->with(['establishment', 'orderItems.product'])
            ->where('public_token', $token)
            ->firstOrFail();

        return view('public.tracking.show', [
            'title' => 'Seguimiento del pedido',
            'publicEstablishment' => $order->establishment,
            'order' => $order,
            'phone' => Str::mask($order->customer_phone, '*', 0, -3),
            'address' => Str::mask($order->delivery_address, '*', 6),
        ]);
    }
}
