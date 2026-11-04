<x-layouts::app :title="'Inicio'">
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Panel principal" subtitle="Resumen de la actividad de tu negocio.">
            <x-ui.button :href="route('orders.index')" icon="clipboard-document-list" wire:navigate>Ver pedidos</x-ui.button>
        </x-page-header>

        @php
            $statusItems = [
                ['label' => 'Pendientes', 'value' => (int) $orderStatusCounts->get('pending', 0), 'color' => 'bg-amber-500'],
                ['label' => 'Confirmados', 'value' => (int) $orderStatusCounts->get('confirmed', 0), 'color' => 'bg-sky-500'],
                ['label' => 'En preparación', 'value' => (int) $orderStatusCounts->get('preparing', 0), 'color' => 'bg-blue-600'],
                ['label' => 'Enviados', 'value' => (int) $orderStatusCounts->get('shipped', 0), 'color' => 'bg-violet-500'],
                ['label' => 'Entregados', 'value' => (int) $orderStatusCounts->get('delivered', 0), 'color' => 'bg-emerald-500'],
                ['label' => 'Cancelados', 'value' => (int) $orderStatusCounts->get('cancelled', 0), 'color' => 'bg-red-500'],
            ];
        @endphp

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicadores">
            <x-stat-card label="Ventas de hoy" :value="'$ '.number_format((float) $salesToday, 0, ',', '.')" hint="Pedidos no cancelados" tone="green" icon="banknotes" />
            <x-stat-card label="Pedidos de hoy" :value="$ordersToday" hint="Recibidos hoy" icon="calendar-days" />
            <x-stat-card label="Pedidos pendientes" :value="$pendingOrderCount" hint="Requieren atención" tone="amber" icon="clock" />
            <x-stat-card label="Pedidos completados" :value="$completedOrderCount" hint="Entregados" tone="green" icon="check-badge" />
            <x-stat-card label="Total de pedidos" :value="$orderCount" hint="Registrados" icon="clipboard-document-list" />
            <x-stat-card label="Productos" :value="$productCount" hint="Registrados" tone="violet" icon="shopping-bag" />
            <x-stat-card label="Categorías" :value="$categoryCount" hint="Creadas" tone="violet" icon="archive-box" />
            <x-stat-card label="Establecimientos" :value="$establishmentCount" hint="A tu cargo" icon="building-storefront" />
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="nyva-card p-5 lg:col-span-2">
                <x-ui.section-header title="Ventas de los últimos 7 días" subtitle="Pedidos no cancelados" class="mb-5" />
                <x-bar-chart :points="$salesChart" />
            </section>

            <section class="nyva-card p-5">
                <x-ui.section-header title="Estado de los pedidos" :subtitle="$orderCount.' en total'" class="mb-5" />
                <x-status-bars :items="$statusItems" />
            </section>
        </div>

        <section class="nyva-card overflow-hidden">
            <x-ui.section-header title="Pedidos recientes" subtitle="Los últimos cinco pedidos" class="p-5">
                <a href="{{ route('orders.index') }}" wire:navigate class="nyva-link text-sm">Ver todos</a>
            </x-ui.section-header>

            @if ($recentOrders->isEmpty())
                <x-ui.empty-state icon="clipboard-document-list" title="Todavía no hay pedidos" description="Cuando recibas pedidos, aparecerán aquí." />
            @else
                <div class="hidden overflow-x-auto md:block">
                    <table class="nyva-table">
                        <thead>
                            <tr><th>Pedido</th><th>Cliente</th><th>Establecimiento</th><th>Total</th><th>Estado</th><th>Fecha</th><th class="text-right">Acción</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($recentOrders as $order)
                                <tr>
                                    <td class="font-semibold text-slate-800">#{{ $order->id }}</td>
                                    <td class="whitespace-nowrap">{{ $order->customer_name }}</td>
                                    <td class="whitespace-nowrap">{{ $order->establishment?->name }}</td>
                                    <td class="whitespace-nowrap font-semibold text-slate-800">$ {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                                    <td><x-order-status :status="$order->status" :label="$order->statusLabel()" /></td>
                                    <td class="whitespace-nowrap">{{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td class="text-right"><a href="{{ route('orders.index', ['order' => $order->id]) }}" class="nyva-link">Ver detalle</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 border-t border-slate-100 md:hidden">
                    @foreach ($recentOrders as $order)
                        <a href="{{ route('orders.index', ['order' => $order->id]) }}" class="flex items-center justify-between gap-3 px-5 py-4 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-800">#{{ $order->id }} · {{ $order->customer_name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $order->ordered_at?->format('d/m/Y H:i') }} · $ {{ number_format((float) $order->total, 0, ',', '.') }}</span>
                            </span>
                            <x-order-status :status="$order->status" :label="$order->statusLabel()" />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="nyva-card p-5">
            <x-ui.section-header title="Suscripciones" subtitle="Planes de tus establecimientos" class="mb-3" />

            @forelse ($subscriptions as $subscription)
                @php
                    $expired = $subscription->end_date?->endOfDay()->isPast();
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 py-3 text-sm first:border-0">
                    <span>
                        <span class="font-semibold text-slate-800">{{ $subscription->plan?->name ?? 'Plan sin nombre' }}</span>
                        <span class="text-slate-500">· {{ $subscription->establishment?->name }}</span>
                    </span>

                    <x-ui.badge :tone="$expired ? 'red' : 'green'">
                        {{ $subscription->end_date ? ($expired ? 'Venció el ' : 'Vence el ').$subscription->end_date->format('d/m/Y') : 'Sin vencimiento' }}
                    </x-ui.badge>
                </div>
            @empty
                <p class="text-sm text-slate-500">No tienes suscripciones registradas.</p>
            @endforelse
        </section>
    </div>
</x-layouts::app>
