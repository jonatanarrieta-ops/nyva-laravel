<?php

namespace App\Notifications;

use App\Models\Order;

class NewOrderReceived extends InternalNotification
{
    public function __construct(private Order $order) {}

    protected function title(): string
    {
        return "Nuevo pedido #{$this->order->id}";
    }

    protected function message(): string
    {
        return 'Total: $ '.number_format((float) $this->order->total, 0, ',', '.');
    }

    protected function url(): ?string
    {
        return route('orders.index', ['order' => $this->order->id], false);
    }
}
