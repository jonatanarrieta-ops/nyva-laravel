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

<a href="{{ route('cart.index') }}" wire:navigate class="inline-flex items-center gap-1.5 hover:text-zinc-900">
    Carrito
    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-xs font-semibold text-indigo-800">
        {{ $count }}
    </span>
</a>
