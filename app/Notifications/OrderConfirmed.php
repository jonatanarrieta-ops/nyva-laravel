<?php

namespace App\Notifications;

use App\Models\Order;

class OrderConfirmed extends InternalNotification
{
    public function __construct(private Order $order) {}

    protected function title(): string
    {
        return "Recibimos tu pedido #{$this->order->id}";
    }

    protected function message(): string
    {
        return 'Te avisaremos cuando cambie su estado.';
    }

    protected function url(): ?string
    {
        return route('my-orders.show', $this->order->id, false);
    }
}
