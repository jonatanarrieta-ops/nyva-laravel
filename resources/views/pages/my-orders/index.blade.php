<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] #[Title('Mis pedidos')] class extends Component {
    use WithPagination;

    public function with(): array
    {
        return [
            'orders' => Auth::user()->orders()
                ->with('establishment')
                ->orderByDesc('ordered_at')
                ->paginate(10),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">Mis pedidos</h1>
        <p class="mt-1 text-sm text-zinc-500">Consulta tus pedidos y su estado.</p>
    </div>

    @if ($orders->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center">
            <p class="font-medium text-zinc-700">Todavía no has realizado pedidos.</p>
            <a href="{{ route('home') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-indigo-700 hover:underline">Ver establecimientos</a>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($orders as $order)
                <a
                    href="{{ route('my-orders.show', $order->id) }}"
                    wire:navigate
                    wire:key="order-{{ $order->id }}"
                    class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-indigo-300 sm:flex-row sm:items-center sm:justify-between"
                >
                    <span class="min-w-0">
                        <span class="block font-semibold text-zinc-900">Pedido #{{ $order->id }}</span>
                        <span class="block text-sm text-zinc-500">
                            {{ $order->establishment?->name }} · {{ $order->ordered_at?->format('d/m/Y H:i') }}
                        </span>
                    </span>

                    <span class="flex items-center justify-between gap-4 sm:justify-end">
                        <x-order-status :status="$order->status" :label="$order->statusLabel()" />
                        <span class="font-semibold text-zinc-900">$ {{ number_format((float) $order->total, 0, ',', '.') }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</div>
