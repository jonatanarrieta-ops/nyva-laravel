<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Pedido realizado')] class extends Component {
    #[Locked]
    public int $orderId;

    public function mount($order): void
    {
        $this->orderId = Auth::user()->orders()->findOrFail($order)->id;
    }

    public function with(): array
    {
        return [
            'order' => Auth::user()->orders()->with('establishment')->findOrFail($this->orderId),
        ];
    }
};
?>

<section class="mx-auto max-w-lg rounded-xl border border-zinc-200 bg-white p-6 text-center shadow-sm">
    <h1 class="text-2xl font-bold text-zinc-900">Pedido realizado correctamente</h1>

    <dl class="mt-6 flex flex-col gap-3 text-left text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-zinc-500">Número de pedido</dt>
            <dd class="font-semibold text-zinc-900">#{{ $order->id }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-zinc-500">Establecimiento</dt>
            <dd class="text-right font-semibold text-zinc-900">{{ $order->establishment?->name }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-zinc-500">Total</dt>
            <dd class="font-semibold text-zinc-900">$ {{ number_format((float) $order->total, 0, ',', '.') }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-zinc-500">Estado</dt>
            <dd class="font-semibold text-zinc-900">{{ $order->statusLabel() }}</dd>
        </div>
    </dl>

    <a href="{{ route('my-orders.show', $order->id) }}" wire:navigate class="mt-6 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
        Seguir mi pedido
    </a>
</section>
