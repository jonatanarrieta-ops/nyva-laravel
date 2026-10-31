<?php

use App\Models\Establishment;
use App\Services\Cart;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Carrito')] class extends Component {
    public function increase(int $productId, Cart $cart): void
    {
        $cart->increase($productId);
        $this->dispatch('cart-updated');
    }

    public function decrease(int $productId, Cart $cart): void
    {
        $cart->decrease($productId);
        $this->dispatch('cart-updated');
    }

    public function remove(int $productId, Cart $cart): void
    {
        $cart->remove($productId);
        $this->dispatch('cart-updated');
    }

    public function clear(Cart $cart): void
    {
        $cart->clear();
        $this->dispatch('cart-updated');
    }

    public function with(Cart $cart): array
    {
        $lines = $cart->lines();

        return [
            'lines' => $lines,
            'total' => $lines->sum('subtotal'),
            'establishment' => Establishment::find($cart->establishmentId()),
            'hasUnavailable' => $lines->contains('orderable', false),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">Carrito</h1>

        @if ($establishment)
            <p class="mt-1 text-sm text-zinc-500">
                Pedido para
                <a href="{{ route('catalog.show', $establishment) }}" wire:navigate class="font-medium text-indigo-700 hover:underline">{{ $establishment->name }}</a>
            </p>
        @endif
    </div>

    @if ($lines->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center">
            <p class="font-medium text-zinc-700">Tu carrito está vacío.</p>
            <a href="{{ route('home') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-indigo-700 hover:underline">Ver establecimientos</a>
        </div>
    @else
        @if ($hasUnavailable)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Algunos productos ya no están disponibles. Elimínalos para continuar.
            </div>
        @endif

        <div class="flex flex-col gap-3">
            @foreach ($lines as $line)
                <article wire:key="line-{{ $line['product']->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-zinc-900">{{ $line['product']->name }}</h2>
                            <p class="text-sm text-zinc-500">$ {{ number_format((float) $line['product']->price, 0, ',', '.') }} c/u</p>

                            @unless ($line['orderable'])
                                <span class="mt-1 inline-block rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700">No disponible</span>
                            @endunless
                        </div>

                        <button type="button" wire:click="remove({{ $line['product']->id }})" class="text-sm font-medium text-red-600 hover:text-red-700">
                            Eliminar
                        </button>
                    </div>

                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrease({{ $line['product']->id }})" aria-label="Disminuir cantidad" class="size-9 rounded-lg bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200">-</button>
                            <span class="w-8 text-center text-sm font-semibold">{{ $line['quantity'] }}</span>
                            <button type="button" wire:click="increase({{ $line['product']->id }})" aria-label="Aumentar cantidad" class="size-9 rounded-lg bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200">+</button>
                        </div>

                        <span class="font-semibold text-zinc-900">$ {{ number_format($line['subtotal'], 0, ',', '.') }}</span>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="font-semibold text-zinc-900">Total</span>
                <span class="text-xl font-bold text-zinc-900">$ {{ number_format($total, 0, ',', '.') }}</span>
            </div>

            <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                <button
                    type="button"
                    wire:click="clear"
                    wire:confirm="¿Seguro que deseas vaciar el carrito?"
                    class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50"
                >
                    Vaciar carrito
                </button>

                @if ($hasUnavailable)
                    <span class="rounded-lg bg-zinc-200 px-5 py-2.5 text-center text-sm font-semibold text-zinc-500">Continuar al pago</span>
                @else
                    <a href="{{ route('checkout') }}" wire:navigate class="rounded-lg bg-indigo-600 px-5 py-2.5 text-center text-sm font-semibold text-white hover:bg-indigo-700">
                        Continuar al pago
                    </a>
                @endif
            </div>
        </section>
    @endif
</div>
