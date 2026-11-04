<x-layouts::app :title="'Inicio'">
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Panel principal" subtitle="Resumen de la actividad de tu negocio.">
            <a href="{{ route('orders.index') }}" wire:navigate class="nyva-btn">Ver pedidos</a>
        </x-page-header>

        @php
            $statusItems = [
                ['label' => 'Pendientes', 'value' => (int) $orderStatusCounts->get('pending', 0), 'color' => 'bg-amber-500'],
                ['label' => 'Confirmados', 'value' => (int) $orderStatusCounts->get('confirmed', 0), 'color' => 'bg-sky-500'],
                ['label' => 'En preparación', 'value' => (int) $orderStatusCounts->get('preparing', 0), 'color' => 'bg-blue-600'],
                ['label' => 'Enviados', 'value' => (int) $orderStatusCounts->get('shipped', 0), 'color' => 'bg-violet-500'],
                ['label' => 'Entregados', 'value' => (int) $orderStatusCounts->get('delivered', 0), 'color' => 'bg-green-500'],
                ['label' => 'Cancelados', 'value' => (int) $orderStatusCounts->get('cancelled', 0), 'color' => 'bg-red-500'],
            ];
        @endphp

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Ventas de hoy" :value="'$ '.number_format((float) $salesToday, 0, ',', '.')" :hint="$ordersToday.($ordersToday === 1 ? ' pedido hoy' : ' pedidos hoy')" tone="green" />
            <x-stat-card label="Pedidos pendientes" :value="$pendingOrderCount" hint="Requieren atención" tone="amber" />
            <x-stat-card label="Pedidos completados" :value="$completedOrderCount" :hint="$orderCount.' pedidos en total'" />
            <x-stat-card label="Productos" :value="$productCount" :hint="$categoryCount.' categorías · '.$establishmentCount.' establecimientos'" tone="violet" />
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="nyva-card p-5 lg:col-span-2">
                <h2 class="mb-4 font-semibold">Ventas de los últimos 7 días</h2>
                <x-bar-chart :points="$salesChart" />
            </section>

            <section class="nyva-card p-5">
                <h2 class="mb-4 font-semibold">Estado de los pedidos</h2>
                <x-status-bars :items="$statusItems" />
            </section>
        </div>

        <section class="nyva-card overflow-hidden">
            <div class="flex items-center justify-between p-5 pb-3">
                <h2 class="font-semibold">Pedidos recientes</h2>
                <a href="{{ route('orders.index') }}" wire:navigate class="nyva-link text-sm">Ver todos</a>
            </div>

            @if ($recentOrders->isEmpty())
                <p class="p-10 text-center text-sm text-zinc-500">Cuando recibas pedidos, aparecerán aquí.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="nyva-table">
                        <thead>
                            <tr><th>Pedido</th><th>Cliente</th><th>Establecimiento</th><th>Total</th><th>Estado</th><th>Fecha</th><th class="text-right">Acción</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td class="font-medium">#{{ $order->id }}</td>
                                    <td class="whitespace-nowrap">{{ $order->customer_name }}</td>
                                    <td class="whitespace-nowrap">{{ $order->establishment?->name }}</td>
                                    <td class="whitespace-nowrap font-medium">$ {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                                    <td><x-order-status :status="$order->status" :label="$order->statusLabel()" /></td>
                                    <td class="whitespace-nowrap">{{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-right"><a href="{{ route('orders.index', ['order' => $order->id]) }}" class="nyva-link">Ver detalle</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="nyva-card p-5">
            <h2 class="mb-4 font-semibold">Suscripciones</h2>

            @forelse ($subscriptions as $subscription)
                @php
                    $expired = $subscription->end_date?->endOfDay()->isPast();
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 py-3 text-sm last:border-0 dark:border-zinc-800">
                    <span>
                        <span class="font-medium">{{ $subscription->plan?->name ?? 'Plan sin nombre' }}</span>
                        <span class="text-zinc-500">· {{ $subscription->establishment?->name }}</span>
                    </span>
                    <span class="text-zinc-500">
                        {{ $subscription->end_date ? ($expired ? 'Venció el ' : 'Vence el ').$subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-zinc-500">No tienes suscripciones registradas.</p>
            @endforelse
        </section>
    </div>
</x-layouts::app>
