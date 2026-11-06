@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header :title="'Pedido #'.$order->id" :subtitle="($order->establishment?->name ?? '—').' · '.($order->ordered_at?->format('d/m/Y H:i') ?? '—')">
            <x-ui.button variant="secondary" :href="route('orders.index')">Volver a pedidos</x-ui.button>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <section class="nyva-card flex flex-wrap items-center gap-3 p-5">
            <span class="text-sm font-semibold text-slate-800">Estado:</span>
            <x-order-status :status="$order->status" :label="$order->statusLabel()" />
            @include('orders._status-form')
        </section>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <section class="nyva-card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Datos de entrega</h2>
                <p class="text-sm text-slate-600">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                <p class="text-sm text-slate-600">{{ $order->delivery_address ?: 'Sin dirección de entrega' }}</p>
            </section>

            <section class="nyva-card p-5">
                <h2 class="mb-3 text-sm font-semibold text-slate-800">Productos</h2>

                <div class="flex flex-col gap-1.5">
                    @foreach ($order->orderItems as $item)
                        <div class="flex justify-between gap-4 text-sm">
                            <span class="text-slate-700">{{ $item->product?->name ?? 'Producto eliminado' }} × {{ $item->quantity }}</span>
                            <span class="font-medium text-slate-800">$ {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4">
                    <span class="font-semibold text-slate-800">Total</span>
                    <span class="text-xl font-bold text-slate-800">$ {{ number_format((float) $order->total, 0, ',', '.') }}</span>
                </div>
            </section>
        </div>
    </div>
@endsection
