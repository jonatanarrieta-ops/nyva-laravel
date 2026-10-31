<?php

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Services\Cart;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.public')] #[Title('Confirmar pedido')] class extends Component {
    public string $customer_name = '';
    public string $customer_phone = '';
    public string $delivery_address = '';

    public function mount(Cart $cart)
    {
        if ($cart->quantities() === []) {
            return $this->redirectRoute('cart.index', navigate: true);
        }

        $this->customer_name = trim(Auth::user()->first_name.' '.Auth::user()->last_name);
    }

    public function placeOrder(Cart $cart, PlaceOrder $placeOrder)
    {
        $order = $placeOrder->handle(
            (int) $cart->establishmentId(),
            $cart->quantities(),
            [
                'customer_name' => trim($this->customer_name),
                'customer_phone' => trim($this->customer_phone),
                'delivery_address' => trim($this->delivery_address),
            ],
            Auth::id(),
        );

        $cart->clear();

        return $this->redirectRoute('my-orders.confirmation', ['order' => $order->id], navigate: true);
    }

    public function with(Cart $cart): array
    {
        $lines = $cart->lines();

        return [
            'lines' => $lines,
            'total' => $lines->sum('subtotal'),
            'establishment' => Establishment::find($cart->establishmentId()),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">Confirmar pedido</h1>
        <p class="mt-1 text-sm text-zinc-500">Revisa tu pedido y completa los datos de entrega.</p>
    </div>

    @error('cart')
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $message }}
            <a href="{{ route('cart.index') }}" wire:navigate class="font-semibold underline">Revisar carrito</a>
        </div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900">Datos de entrega</h2>

            <form wire:submit="placeOrder" id="checkout-form" class="mt-4 flex flex-col gap-4">
                <div>
                    <label for="customer_name" class="mb-1.5 block text-sm font-medium text-zinc-700">Nombre del cliente</label>
                    <input id="customer_name" type="text" wire:model="customer_name" required maxlength="150" autocomplete="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                    @error('customer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-zinc-700">Teléfono</label>
                    <input id="customer_phone" type="tel" wire:model="customer_phone" required maxlength="20" autocomplete="tel" placeholder="Ej. 3001234567" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                    @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="delivery_address" class="mb-1.5 block text-sm font-medium text-zinc-700">Dirección de entrega</label>
                    <input id="delivery_address" type="text" wire:model="delivery_address" required maxlength="255" autocomplete="street-address" placeholder="Ej. Calle 10 # 20-30" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                    @error('delivery_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-900">Resumen</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ $establishment?->name }}</p>

            <div class="mt-4 flex flex-col gap-3">
                @foreach ($lines as $line)
                    <div wire:key="line-{{ $line['product']->id }}" class="flex justify-between gap-4 text-sm">
                        <span class="text-zinc-700">{{ $line['product']->name }} × {{ $line['quantity'] }}</span>
                        <span class="whitespace-nowrap font-medium text-zinc-900">$ {{ number_format($line['subtotal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-zinc-200 pt-4">
                <span class="font-semibold text-zinc-900">Total</span>
                <span class="text-xl font-bold text-zinc-900">$ {{ number_format($total, 0, ',', '.') }}</span>
            </div>

            <button
                type="submit"
                form="checkout-form"
                wire:loading.attr="disabled"
                wire:target="placeOrder"
                class="mt-4 w-full rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="placeOrder">Confirmar pedido</span>
                <span wire:loading wire:target="placeOrder">Procesando...</span>
            </button>
        </section>
    </div>
</div>
