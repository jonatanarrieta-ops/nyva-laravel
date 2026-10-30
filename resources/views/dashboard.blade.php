<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-8 p-4 sm:p-6 lg:p-8">

       

        {{-- Indicadores --}}
        <section>
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    Resumen general
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    Estado actual de tu operación.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                @foreach ([
                    [
                        'label' => 'Establecimientos',
                        'value' => $establishmentCount,
                        'icon' => 'building-storefront',
                        'detail' => 'Negocios registrados',
                    ],
                    [
                        'label' => 'Categorías',
                        'value' => $categoryCount,
                        'icon' => 'squares-2x2',
                        'detail' => 'Categorías creadas',
                    ],
                    [
                        'label' => 'Productos',
                        'value' => $productCount,
                        'icon' => 'shopping-bag',
                        'detail' => 'Productos registrados',
                    ],
                    [
                        'label' => 'Pedidos pendientes',
                        'value' => $pendingOrderCount,
                        'icon' => 'clock',
                        'detail' => $orderCount . ' pedidos en total',
                    ],
                ] as $stat)
                    <article
                        class="group rounded-xl border-zinc-200/70 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                {{ $stat['label'] }}
                            </p>

                            <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                <flux:icon :name="$stat['icon']" class="size-5" />
                            </span>
                        </div>

                        <p class="mt-5 pl-1 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                            {{ number_format($stat['value']) }}
                        </p>

                    </article>
                @endforeach

            </div>
        </section>

        

        {{-- Paneles secundarios --}}
        <section class="mt-4 grid grid-cols-1 gap-6 xl:grid-cols-5">

            {{-- Establecimientos --}}
            <article class="rounded-xl  border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 xl:col-span-3">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Tus establecimientos
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Administra los negocios asociados a tu cuenta.
                        </p>
                    </div>

                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="building-storefront" class="size-5" />
                    </span>
                </div>

            <div class="mt-6 flex min-h-48 flex-col items-start rounded-xl border border-zinc-200/70 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">

                {{-- Información del establecimiento --}}
                <div>
                    <p class="text-3xl font-bold leading-none tracking-tight text-zinc-700 dark:text-zinc-2000">
                        {{ number_format($establishmentCount) }}
                    </p>

                    <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-2000">
                        Establecimientos registrados
                    </p>
                </div>

            </div>

            <div class="mt-5">
                <flux:button
                    :href="route('establishments.index')"
                    variant="outline"
                    icon="arrow-right"
                    wire:navigate
                >
                    Gestionar establecimientos
                </flux:button>
            </div>            

            </article>

            {{-- Suscripciones --}}
            <article class="rounded-xl border-zinc-200/70 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 xl:col-span-2">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            Suscripciones
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Planes asociados a tus negocios.
                        </p>
                    </div>

                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="credit-card" class="size-5" />
                    </span>
                </div>

                <div class="mt-5">
                    @forelse ($subscriptions as $subscription)
                        <div class=" border-zinc-100 py-4 last:border-0 dark:border-zinc-800">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $subscription->plan?->name ?? 'Plan no disponible' }}
                                </p>

                                <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $subscription->status }}
                                </span>
                            </div>

                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                Periodo: {{ $subscription->billing_period }}
                            </p>
                        </div>
                    @empty
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                No hay suscripciones registradas para tus establecimientos.
                            </p>
                        </div>
                    @endforelse
                </div>
            </article>

        </section>
    </div>
</x-layouts::app>
