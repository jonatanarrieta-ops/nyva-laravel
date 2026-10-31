<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-8 rounded-xl bg-gray-50 p-4 sm:p-6 lg:p-8">

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900">
                Panel principal
            </h1>
            <p class="mt-1 text-sm text-zinc-500">
                Resumen de la actividad de tus establecimientos.
            </p>
        </div>

        @php
            $statusLabels = [
                'pending' => 'Pendiente',
                'confirmed' => 'Confirmado',
                'preparing' => 'En preparación',
                'shipped' => 'En camino',
                'delivered' => 'Entregado',
                'cancelled' => 'Cancelado',
            ];

            $statusColors = [
                'pending' => 'bg-amber-100 text-amber-800',
                'confirmed' => 'bg-blue-100 text-blue-800',
                'preparing' => 'bg-indigo-100 text-indigo-800',
                'shipped' => 'bg-violet-100 text-violet-800',
                'delivered' => 'bg-green-100 text-green-800',
                'cancelled' => 'bg-red-100 text-red-800',
            ];
        @endphp

        {{-- Indicadores --}}
        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Resumen general
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Indicadores principales de tu operación.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

                @foreach ([
                    ['label' => 'Establecimientos', 'value' => $establishmentCount, 'detail' => 'Negocios registrados'],
                    ['label' => 'Categorías', 'value' => $categoryCount, 'detail' => 'Categorías creadas'],
                    ['label' => 'Productos', 'value' => $productCount, 'detail' => 'Productos registrados'],
                    ['label' => 'Total de pedidos', 'value' => $orderCount, 'detail' => 'Pedidos registrados'],
                    ['label' => 'Pedidos pendientes', 'value' => $pendingOrderCount, 'detail' => 'Requieren atención'],
                    ['label' => 'Pedidos de hoy', 'value' => $ordersToday, 'detail' => 'Pedidos recibidos hoy'],
                    ['label' => 'Ventas de hoy', 'value' => '$ ' . number_format((float) $salesToday, 0, ',', '.'), 'detail' => 'Total no cancelado'],
                ] as $stat)
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-zinc-500">
                            {{ $stat['label'] }}
                        </p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-zinc-900">
                            {{ $stat['value'] }}
                        </p>
                        <p class="mt-2 text-xs text-zinc-500">
                            {{ $stat['detail'] }}
                        </p>
                    </div>
                @endforeach

            </div>
        </section>

        {{-- Resumen de pedidos por estado --}}
        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Estado de los pedidos
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Distribución de todos tus pedidos registrados.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['status' => 'pending', 'label' => 'Pendientes', 'color' => 'bg-amber-500'],
                    ['status' => 'preparing', 'label' => 'En preparación', 'color' => 'bg-indigo-500'],
                    ['status' => 'shipped', 'label' => 'Enviados', 'color' => 'bg-violet-500'],
                    ['status' => 'delivered', 'label' => 'Entregados', 'color' => 'bg-green-500'],
                ] as $item)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full {{ $item['color'] }}"></span>
                            <span class="text-sm font-medium text-zinc-600">
                                {{ $item['label'] }}
                            </span>
                        </div>
                        <p class="mt-3 text-2xl font-bold text-zinc-900">
                            {{ $orderStatusCounts->get($item['status'], 0) }}
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-4 text-sm">
                <span class="text-zinc-500">
                    Pedidos cancelados:
                    <strong class="text-zinc-800">
                        {{ $orderStatusCounts->get('cancelled', 0) }}
                    </strong>
                </span>
                <span class="text-zinc-500">
                    Total registrado:
                    <strong class="text-zinc-900">
                        {{ $orderCount }}
                    </strong>
                </span>
            </div>
        </section>

        {{-- Pedidos recientes --}}
        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900">
                        Pedidos recientes
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500">
                        Los últimos cinco pedidos registrados.
                    </p>
                </div>

                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Ver todos los pedidos
                </a>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="p-10 text-center">
                    <p class="font-medium text-zinc-700">
                        Todavía no tienes pedidos registrados.
                    </p>
                    <p class="mt-1 text-sm text-zinc-500">
                        Cuando recibas pedidos, aparecerán aquí.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Cliente
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Establecimiento
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Total
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Estado
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Fecha
                                </th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-zinc-500">
                                    Acción
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($recentOrders as $order)
                                <tr class="hover:bg-zinc-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-zinc-900">
                                        {{ $order->customer_name }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-zinc-600">
                                        {{ $order->establishment?->name ?? 'Sin establecimiento' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-zinc-900">
                                        $ {{ number_format((float) $order->total, 0, ',', '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-zinc-100 text-zinc-700' }}">
                                            {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-zinc-600">
                                        {{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right text-sm">
                                        <a href="{{ route('orders.index', ['order' => $order->id]) }}"
                                           class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-2 font-semibold text-indigo-700 hover:bg-indigo-100">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Establecimientos y suscripciones --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Tus establecimientos
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Administra los negocios registrados en NYVA.
                </p>

                <div class="mt-5 flex items-end justify-between">
                    <div>
                        <p class="text-3xl font-bold text-zinc-900">
                            {{ $establishmentCount }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Establecimientos registrados
                        </p>
                    </div>

                    <a href="{{ route('establishments.index') }}"
                       class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                        Gestionar
                    </a>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-900">
                    Suscripciones
                </h2>
                <p class="mt-1 text-sm text-zinc-500">
                    Planes asociados a tus establecimientos.
                </p>

                @forelse ($subscriptions as $subscription)
                    <div class="mt-4 flex flex-col gap-2 border-b border-zinc-100 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-zinc-900">
                                {{ $subscription->plan?->name ?? 'Plan sin nombre' }}
                            </span>
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                {{ ucfirst($subscription->status ?? 'Sin estado') }}
                            </span>
                        </div>
                        <p class="text-sm text-zinc-500">
                            Periodo: {{ $subscription->billing_period ?? 'No especificado' }}
                        </p>
                    </div>
                @empty
                    <p class="mt-5 text-sm text-zinc-500">
                        No tienes suscripciones registradas.
                    </p>
                @endforelse
            </section>

        </div>
    </div>
</x-layouts::app>