@extends('layouts.public')

@section('content')
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Confirmar pedido</h1>
            <p class="mt-1 text-sm text-zinc-500">Revisa tu pedido y completa los datos de entrega.</p>
        </div>

        @error('cart')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $message }}
                <a href="{{ route('cart.index') }}" class="font-semibold underline">Revisar carrito</a>
            </div>
        @enderror

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">Datos de entrega</h2>

                <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form" class="mt-4 flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="order_key" value="{{ old('order_key', $orderKey) }}">

                    <div>
                        <label for="customer_name" class="mb-1.5 block text-sm font-medium text-zinc-700">Nombre del cliente</label>
                        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $customerName) }}" required maxlength="150" autocomplete="name" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                        @error('customer_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-zinc-700">Teléfono</label>
                        <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" required maxlength="20" autocomplete="tel" placeholder="Ej. 3001234567" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                        @error('customer_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="delivery_address" class="mb-1.5 block text-sm font-medium text-zinc-700">Dirección de entrega</label>
                        <input id="delivery_address" name="delivery_address" type="text" value="{{ old('delivery_address') }}" required maxlength="255" autocomplete="street-address" placeholder="Ej. Calle 10 # 20-30" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm">
                        @error('delivery_address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">Resumen</h2>
                <p class="mt-1 text-sm text-zinc-500">{{ $establishment?->name }}</p>

                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($lines as $line)
                        <div class="flex justify-between gap-4 text-sm">
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
                    class="mt-4 w-full rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
                >
                    Confirmar pedido
                </button>
            </section>
        </div>
    </div>
@endsection
