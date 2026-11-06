@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Pedidos" subtitle="Administra y registra los pedidos de tus establecimientos.">
            <x-ui.button :href="route('orders.create')" icon="plus">Nuevo pedido</x-ui.button>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <form method="GET" action="{{ route('orders.index') }}" class="nyva-card grid grid-cols-1 items-end gap-3 p-4 md:grid-cols-[1fr_1fr_1fr_auto]">
            <x-ui.input label="Buscar cliente" name="search" type="search" :value="$search" placeholder="Nombre o teléfono" />

            <x-ui.select label="Establecimiento" name="establishment">
                <option value="">Todos</option>
                @foreach ($establishments as $establishment)
                    <option value="{{ $establishment->id }}" @selected($establishmentId === (string) $establishment->id)>{{ $establishment->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Estado" name="status">
                <option value="">Todos</option>
                @foreach (\App\Models\Order::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
        </form>

        <section class="nyva-card overflow-hidden">
            @if ($orders->isEmpty())
                <x-ui.empty-state icon="clipboard-document-list" title="No hay pedidos para mostrar" description="Cambia los filtros o registra un pedido nuevo." />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <article class="grid grid-cols-1 gap-3 p-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_7rem_minmax(0,1.4fr)_auto] md:items-center md:gap-4 md:px-5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800">#{{ $order->id }} · {{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->customer_phone }} · {{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>

                            <p class="truncate text-sm text-slate-600">{{ $order->establishment?->name ?? '—' }}</p>

                            <p class="font-semibold text-slate-800">$ {{ number_format((float) $order->total, 0, ',', '.') }}</p>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-order-status :status="$order->status" :label="$order->statusLabel()" />
                                @include('orders._status-form')
                            </div>

                            <a href="{{ route('orders.show', $order->id) }}" class="nyva-link justify-self-start text-sm md:justify-self-end">Ver detalle</a>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($orders->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        </section>
    </div>
@endsection
