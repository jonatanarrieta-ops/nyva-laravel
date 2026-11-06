@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Nuevo pedido" subtitle="Registra los datos del cliente y las cantidades de cada producto.">
            <x-ui.button variant="secondary" :href="route('orders.index')">Cancelar</x-ui.button>
        </x-page-header>

        <form method="GET" action="{{ route('orders.create') }}" class="nyva-card grid grid-cols-1 items-end gap-3 p-4 md:grid-cols-[1fr_auto]">
            <x-ui.select label="Establecimiento" name="establishment">
                <option value="">Selecciona un establecimiento</option>
                @foreach ($establishments as $option)
                    <option value="{{ $option->id }}" @selected($establishment?->id === $option->id)>{{ $option->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary">Ver productos</x-ui.button>
        </form>

        @if ($establishment)
            <form method="POST" action="{{ route('orders.store') }}" class="nyva-card flex flex-col gap-6 p-5 md:p-6">
                @csrf
                <input type="hidden" name="establishment_id" value="{{ $establishment->id }}">

                @error('establishment_id')
                    <x-ui.alert tone="error">{{ $message }}</x-ui.alert>
                @enderror

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-ui.input label="Nombre del cliente" name="customer_name" :value="old('customer_name')" placeholder="Ej. Juan Pérez" />
                    <x-ui.input label="Teléfono" name="customer_phone" type="tel" :value="old('customer_phone')" placeholder="Ej. 3001234567" />
                    <x-ui.input label="Dirección de entrega" name="delivery_address" :value="old('delivery_address')" placeholder="Ej. Calle 10 # 20-30" />
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-slate-800">Productos de {{ $establishment->name }}</h3>

                    @error('cart')
                        <x-ui.alert tone="error" class="mb-3">{{ $message }}</x-ui.alert>
                    @enderror

                    @if ($products->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            No hay productos disponibles para este establecimiento.
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                            @foreach ($products as $product)
                                <label for="item-{{ $product->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-slate-800">{{ $product->name }}</span>
                                        <span class="block text-xs text-slate-500">{{ $product->category?->name ?? 'Sin categoría' }} · $ {{ number_format((float) $product->price, 0, ',', '.') }}</span>
                                    </span>
                                    <input id="item-{{ $product->id }}" name="items[{{ $product->id }}]" type="number" min="0" max="{{ \App\Actions\PlaceOrder::MAX_QUANTITY }}" value="{{ old('items.'.$product->id, 0) }}" class="nyva-input w-20! text-center">
                                </label>
                                @error('items.'.$product->id) <p class="nyva-error">{{ $message }}</p> @enderror
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                    <x-ui.button variant="secondary" :href="route('orders.index')">Cancelar</x-ui.button>
                    <x-ui.button type="submit">Crear pedido</x-ui.button>
                </div>
            </form>
        @else
            <div class="nyva-card p-10 text-center text-sm text-slate-500">Selecciona un establecimiento para registrar el pedido.</div>
        @endif
    </div>
@endsection
