<?php

namespace App\Notifications;

use App\Models\Order;

class OrderStatusChanged extends InternalNotification
{
    public function __construct(private Order $order) {}

    protected function title(): string
    {
        return "Pedido #{$this->order->id} actualizado";
    }

    protected function message(): string
    {
        return "Estado: {$this->order->statusLabel()}";
    }

    protected function url(): ?string
    {
        return route('my-orders.show', $this->order->id, false);
    }
}
