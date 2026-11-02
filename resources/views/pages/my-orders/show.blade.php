<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Seguimiento del pedido')] class extends Component {
    #[Locked]
    public int $orderId;

    public function mount($order): void
    {
        $this->orderId = Auth::user()->orders()->findOrFail($order)->id;
    }

    public function with(): array
    {
        $order = Auth::user()->orders()
            ->with(['establishment', 'orderItems.product'])
            ->findOrFail($this->orderId);

        return [
            'order' => $order,
            'itemsSubtotal' => $order->orderItems->sum('subtotal'),
        ];
    }
};
?>

<div wire:poll.20s class="flex flex-col gap-6">
    <div>
        <a href="{{ route('my-orders.index') }}" wire:navigate class="text-sm font-medium text-indigo-700 hover:underline">Volver a mis pedidos</a>

        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-zinc-900">Pedido #{{ $order->id }}</h1>
            <x-order-status :status="$order->status" :label="$order->statusLabel()" />
        </div>

        <p class="mt-1 text-sm text-zinc-500">
            {{ $order->establishment?->name }} · {{ $order->ordered_at?->format('d/m/Y H:i') }}
        </p>
    </div>

    <x-order-tracker :order="$order" />

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900">Productos</h2>

            <div class="mt-4 flex flex-col gap-3">
                @foreach ($order->orderItems as $item)
                    <div wire:key="item-{{ $item->id }}" class="flex justify-between gap-4 text-sm">
                        <span class="text-zinc-700">
                            {{ $item->product?->name ?? 'Producto eliminado' }} × {{ $item->quantity }}
                            <span class="block text-xs text-zinc-500">$ {{ number_format((float) $item->unit_price, 0, ',', '.') }} c/u</span>
                        </span>
                        <span class="whitespace-nowrap font-medium text-zinc-900">$ {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-col gap-2 border-t border-zinc-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500">Subtotal</span>
                    <span class="text-zinc-900">$ {{ number_format((float) $itemsSubtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-zinc-900">Total</span>
                    <span class="text-xl font-bold text-zinc-900">$ {{ number_format((float) $order->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900">Entrega</h2>

            <dl class="mt-4 flex flex-col gap-3 text-sm">
                <div>
                    <dt class="text-zinc-500">Cliente</dt>
                    <dd class="font-medium text-zinc-900">{{ $order->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Teléfono</dt>
                    <dd class="font-medium text-zinc-900">{{ $order->customer_phone }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Dirección</dt>
                    <dd class="break-words font-medium text-zinc-900">{{ $order->delivery_address }}</dd>
                </div>
            </dl>
        </section>
    </div>
</div>
