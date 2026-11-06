@extends('layouts.public')

@section('content')
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Carrito</h1>

            @if ($establishment)
                <p class="mt-1 text-sm text-zinc-500">
                    Pedido para
                    <a href="{{ route('catalog.show', $establishment) }}" class="font-medium text-blue-800 hover:underline">{{ $establishment->name }}</a>
                </p>
            @endif
        </div>

        @if ($lines->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center">
                <p class="font-medium text-zinc-700">Tu carrito está vacío.</p>
            </div>
        @else
            @if ($hasUnavailable)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    Algunos productos ya no están disponibles. Elimínalos para continuar.
                </div>
            @endif

            <div class="flex flex-col gap-3">
                @foreach ($lines as $line)
                    <article class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="font-semibold text-zinc-900">{{ $line['product']->name }}</h2>
                                <p class="text-sm text-zinc-500">$ {{ number_format((float) $line['product']->price, 0, ',', '.') }} c/u</p>

                                @unless ($line['orderable'])
                                    <span class="mt-1 inline-block rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700">No disponible</span>
                                @endunless
                            </div>

                            <x-confirm-action
                                :name="'remove-item-'.$line['product']->id"
                                :action="route('cart.items.destroy', $line['product']->id)"
                                title="¿Eliminar este producto?"
                                :message="'Se quitará '.$line['product']->name.' de tu carrito.'"
                            >
                                <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700">
                                    Eliminar
                                </button>
                            </x-confirm-action>
                        </div>

                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('cart.items.update', $line['product']->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $line['quantity'] - 1 }}">
                                    <button type="submit" aria-label="Disminuir cantidad" class="size-9 rounded-lg bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200">-</button>
                                </form>
                                <span class="w-8 text-center text-sm font-semibold">{{ $line['quantity'] }}</span>
                                <form method="POST" action="{{ route('cart.items.update', $line['product']->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="quantity" value="{{ $line['quantity'] + 1 }}">
                                    <button type="submit" aria-label="Aumentar cantidad" class="size-9 rounded-lg bg-zinc-100 font-bold text-zinc-700 hover:bg-zinc-200">+</button>
                                </form>
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
                    <x-confirm-action
                        name="clear-cart"
                        :action="route('cart.clear')"
                        title="¿Vaciar el carrito?"
                        message="Se quitarán todos los productos de tu pedido."
                        confirm="Vaciar carrito"
                    >
                        <button type="button" class="w-full rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">
                            Vaciar carrito
                        </button>
                    </x-confirm-action>

                    @if ($hasUnavailable)
                        <span class="rounded-lg bg-zinc-200 px-5 py-2.5 text-center text-sm font-semibold text-zinc-500">Continuar al pago</span>
                    @else
                        <a href="{{ route('checkout') }}" class="rounded-lg bg-blue-700 px-5 py-2.5 text-center text-sm font-semibold text-white hover:bg-blue-800">
                            Continuar al pago
                        </a>
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection
