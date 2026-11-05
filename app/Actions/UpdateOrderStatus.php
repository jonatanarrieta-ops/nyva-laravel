<?php

namespace App\Actions;

use App\Models\Order;
use App\Notifications\OrderStatusChanged;

class UpdateOrderStatus
{
    public function handle(Order $order, string $status): bool
    {
        if (! $order->canTransitionTo($status)) {
            return false;
        }

        $order->update(['status' => $status]);

        $order->user?->notify(new OrderStatusChanged($order));

        return true;
    }
}
