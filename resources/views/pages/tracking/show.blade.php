<?php

use App\Models\Order;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Seguimiento del pedido')] class extends Component {
    #[Locked]
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->order();
    }

    private function order(): Order
    {
        return Order::query()
            ->with(['establishment', 'orderItems.product'])
            ->where('public_token', $this->token)
            ->firstOrFail();
    }

    public function rendering($view): void
    {
        $view->layoutData(['publicEstablishment' => $this->order()->establishment]);
    }

    public function with(): array
    {
        $order = $this->order();

        return [
            'order' => $order,
            'phone' => Str::mask($order->customer_phone, '*', 0, -3),
            'address' => Str::mask($order->delivery_address, '*', 6),
        ];
    }
};
?>

<div wire:poll.20s class="flex flex-col gap-6">
    <div>
        <div class="flex flex-wrap items-center gap-3">
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
                        <span class="text-zinc-700">{{ $item->product?->name ?? 'Producto eliminado' }} × {{ $item->quantity }}</span>
                        <span class="whitespace-nowrap font-medium text-zinc-900">$ {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-4">
                <span class="font-semibold text-zinc-900">Total</span>
                <span class="text-xl font-bold text-zinc-900">$ {{ number_format((float) $order->total, 0, ',', '.') }}</span>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900">Entrega</h2>

            <dl class="mt-4 flex flex-col gap-3 text-sm">
                <div>
                    <dt class="text-zinc-500">Teléfono</dt>
                    <dd class="font-medium text-zinc-900">{{ $phone }}</dd>
                </div>
                <div>
                    <dt class="text-zinc-500">Dirección</dt>
                    <dd class="break-words font-medium text-zinc-900">{{ $address }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-zinc-500">Guarda este enlace. Es personal y solo permite consultar el pedido.</p>
        </section>
    </div>
</div>
