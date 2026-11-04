<?php

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Reportes')] class extends Component {
    public string $startDate = '';
    public string $endDate = '';
    public string $establishmentFilter = '';

    public function mount(): void
    {
        abort_unless(
            $this->reportableEstablishments()->contains(fn ($establishment) => $establishment->hasFeature('reports')),
            403,
            'Tu plan no incluye reportes.'
        );

        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    private function reportableEstablishments()
    {
        return Auth::user()->accessibleEstablishments('orders')->orderBy('name')->get();
    }

    private function filteredOrders()
    {
        $query = Order::query()
            ->whereIn(
                'establishment_id',
                Auth::user()->accessibleEstablishments('orders')->select('id')
            )
            ->where('orders.status', '!=', 'cancelled');

        if ($this->startDate && $this->endDate && $this->startDate <= $this->endDate) {
            $query->whereBetween('orders.ordered_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59',
            ]);
        }

        if ($this->establishmentFilter) {
            $query->whereIn(
                'establishment_id',
                Auth::user()->accessibleEstablishments('orders')
                    ->whereKey($this->establishmentFilter)
                    ->select('id')
            );
        }

        return $query;
    }

    #[Computed]
    public function establishmentsList()
    {
        return $this->reportableEstablishments();
    }

    #[Computed]
    public function summary(): array
    {
        $orders = $this->filteredOrders();

        $totalOrders = (clone $orders)->count();
        $totalSales = (float) (clone $orders)->sum('total');

        return [
            'sales' => $totalSales,
            'orders' => $totalOrders,
            'average' => $totalOrders > 0 ? $totalSales / $totalOrders : 0,
            'units' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.id', (clone $orders)->select('id'))
                ->sum('order_items.quantity'),
        ];
    }

    #[Computed]
    public function dailySales()
    {
        return (clone $this->filteredOrders())
            ->selectRaw('DATE(ordered_at) as day, COUNT(*) as orders_count, SUM(total) as sales')
            ->groupByRaw('DATE(ordered_at)')
            ->orderBy('day')
            ->get();
    }

    #[Computed]
    public function topProducts()
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.id', (clone $this->filteredOrders())->select('id'))
            ->select(
                'products.name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.subtotal) as sales')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function topEstablishments()
    {
        return (clone $this->filteredOrders())
            ->join('establishments', 'establishments.id', '=', 'orders.establishment_id')
            ->select(
                'establishments.name',
                DB::raw('COUNT(orders.id) as orders_count'),
                DB::raw('SUM(orders.total) as sales')
            )
            ->groupBy('establishments.id', 'establishments.name')
            ->orderByDesc('sales')
            ->get();
    }

    public function clearFilters(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->establishmentFilter = '';
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
    <x-page-header title="Estadísticas y reportes" subtitle="Consulta las ventas y el rendimiento de tus establecimientos." />

    <section class="nyva-card p-4 md:p-5">
        <x-ui.section-header title="Filtros del reporte" subtitle="Selecciona el periodo y el establecimiento." class="mb-4" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.input label="Fecha inicial" name="startDate" type="date" wire:model.live="startDate" />
            <x-ui.input label="Fecha final" name="endDate" type="date" wire:model.live="endDate" />

            <x-ui.select label="Establecimiento" name="establishmentFilter" wire:model.live="establishmentFilter">
                <option value="">Todos los establecimientos</option>
                @foreach ($this->establishmentsList as $establishment)
                    <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                @endforeach
            </x-ui.select>

            <div class="flex items-end">
                <x-ui.button variant="secondary" wire:click="clearFilters" icon="arrow-path" class="w-full">Limpiar filtros</x-ui.button>
            </div>
        </div>

        @if ($startDate && $endDate && $startDate > $endDate)
            <p class="nyva-error mt-3">La fecha inicial no puede ser posterior a la fecha final.</p>
        @endif
    </section>

    @if (! $startDate || ! $endDate || $startDate > $endDate)
        <x-ui.alert tone="warning">Selecciona un rango de fechas válido para consultar las estadísticas.</x-ui.alert>
    @else
        <div class="flex flex-col gap-6" wire:loading.class="opacity-60" wire:target="startDate, endDate, establishmentFilter, clearFilters">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="Ventas totales" :value="'$ '.number_format($this->summary['sales'], 0, ',', '.')" hint="Pedidos no cancelados" tone="green" icon="banknotes" />
                <x-stat-card label="Pedidos registrados" :value="number_format($this->summary['orders'], 0, ',', '.')" hint="En el periodo seleccionado" icon="clipboard-document-list" />
                <x-stat-card label="Ticket promedio" :value="'$ '.number_format($this->summary['average'], 0, ',', '.')" hint="Venta promedio por pedido" tone="violet" icon="calculator" />
                <x-stat-card label="Unidades vendidas" :value="number_format($this->summary['units'], 0, ',', '.')" hint="Productos en pedidos no cancelados" tone="amber" icon="cube" />
            </section>

            <section class="nyva-card overflow-hidden">
                <x-ui.section-header title="Ventas por día" subtitle="Pedidos no cancelados del periodo" class="p-5" />

                @if ($this->dailySales->isEmpty())
                    <x-ui.empty-state icon="chart-bar" title="No hay ventas en este periodo" description="Prueba con otro rango de fechas." />
                @else
                    <div class="px-5 pb-5">
                        <x-bar-chart :points="$this->dailySales->map(fn ($day) => ['label' => \Carbon\Carbon::parse($day->day)->format('d/m'), 'value' => (float) $day->sales])->values()->all()" />
                    </div>

                    <div class="overflow-x-auto border-t border-slate-100">
                        <table class="nyva-table">
                            <thead><tr><th>Fecha</th><th class="text-center">Pedidos</th><th class="text-right">Ventas</th></tr></thead>
                            <tbody>
                                @foreach ($this->dailySales as $day)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}</td>
                                        <td class="text-center">{{ $day->orders_count }}</td>
                                        <td class="text-right font-semibold text-slate-800">$ {{ number_format($day->sales, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <section class="nyva-card overflow-hidden">
                    <x-ui.section-header title="Productos más vendidos" subtitle="Por unidades" class="p-5" />

                    @if ($this->topProducts->isEmpty())
                        <x-ui.empty-state icon="shopping-bag" title="Sin productos vendidos" description="No hay ventas en este periodo." />
                    @else
                        @php
                            $maxUnits = max(1, $this->topProducts->max('units'));
                        @endphp
                        <ul class="divide-y divide-slate-100 border-t border-slate-100">
                            @foreach ($this->topProducts as $product)
                                <li class="flex flex-col gap-2 px-5 py-3">
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="min-w-0 truncate font-medium text-slate-800">{{ $product->name }}</span>
                                        <span class="whitespace-nowrap text-slate-500">{{ $product->units }} u · <span class="font-semibold text-slate-800">$ {{ number_format($product->sales, 0, ',', '.') }}</span></span>
                                    </div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full bg-blue-600" style="width: {{ round($product->units / $maxUnits * 100) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="nyva-card overflow-hidden">
                    <x-ui.section-header title="Ventas por establecimiento" subtitle="Establecimientos con mayor actividad" class="p-5" />

                    @if ($this->topEstablishments->isEmpty())
                        <x-ui.empty-state icon="building-storefront" title="Sin ventas registradas" description="No hay ventas en este periodo." />
                    @else
                        <div class="overflow-x-auto border-t border-slate-100">
                            <table class="nyva-table">
                                <thead><tr><th>Establecimiento</th><th class="text-center">Pedidos</th><th class="text-right">Ventas</th></tr></thead>
                                <tbody>
                                    @foreach ($this->topEstablishments as $establishment)
                                        <tr>
                                            <td class="font-medium text-slate-800">{{ $establishment->name }}</td>
                                            <td class="text-center">{{ $establishment->orders_count }}</td>
                                            <td class="text-right font-semibold text-slate-800">$ {{ number_format($establishment->sales, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    @endif
</div>
