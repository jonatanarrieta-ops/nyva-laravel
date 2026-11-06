<?php

use App\Services\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        //
    }

    public function with(Cart $cart): array
    {
        return ['count' => $cart->count()];
    }
};
?>

<a href="{{ route('cart.index') }}" wire:navigate aria-label="Carrito, {{ $count }} productos" class="inline-flex items-center gap-2 rounded-lg px-2.5 py-1.5 hover:bg-zinc-100 hover:text-zinc-900">
    <flux:icon name="shopping-cart" class="size-5" />
    <span>Carrito</span>
    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-800">
        {{ $count }}
    </span>
</a>
