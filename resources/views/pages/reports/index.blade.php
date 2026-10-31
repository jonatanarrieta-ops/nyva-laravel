<?php

use App\Models\Establishment;
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
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    private function filteredOrders()
    {
        $query = Order::query()
            ->whereIn(
                'establishment_id',
                Auth::user()->establishments()->select('id')
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
                Auth::user()->establishments()
                    ->whereKey($this->establishmentFilter)
                    ->select('id')
            );
        }

        return $query;
    }

    #[Computed]
    public function establishmentsList()
    {
        return Establishment::query()
            ->where('user_id', Auth::id())
            ->orderBy('name')
            ->get();
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

<div class="space-y-6 p-4 md:p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Estadísticas y reportes</h1>
            <p class="mt-1 text-sm text-zinc-600">
                Consulta las ventas y el rendimiento de tus establecimientos.
            </p>
        </div>
    </div>

    <section class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
        <div class="mb-4">
            <h2 class="font-semibold text-zinc-900">Filtros del reporte</h2>
            <p class="text-sm text-zinc-500">Selecciona el periodo y el establecimiento.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div>
                <label for="startDate" class="mb-1 block text-sm font-medium text-zinc-700">
                    Fecha inicial
                </label>
                <input
                    id="startDate"
                    type="date"
                    wire:model.live="startDate"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900"
                >
            </div>

            <div>
                <label for="endDate" class="mb-1 block text-sm font-medium text-zinc-700">
                    Fecha final
                </label>
                <input
                    id="endDate"
                    type="date"
                    wire:model.live="endDate"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900"
                >
            </div>

            <div>
                <label for="establishmentFilter" class="mb-1 block text-sm font-medium text-zinc-700">
                    Establecimiento
                </label>
                <select
                    id="establishmentFilter"
                    wire:model.live="establishmentFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900"
                >
                    <option value="">Todos los establecimientos</option>
                    @foreach ($this->establishmentsList as $establishment)
                        <option value="{{ $establishment->id }}">
                            {{ $establishment->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="w-full rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-100"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>

        @if ($startDate && $endDate && $startDate > $endDate)
            <p class="mt-3 text-sm text-red-600">
                La fecha inicial no puede ser posterior a la fecha final.
            </p>
        @endif
    </section>

    @if (!$startDate || !$endDate || $startDate > $endDate)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Selecciona un rango de fechas válido para consultar las estadísticas.
        </div>
    @else
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-zinc-500">Ventas totales</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900">
                    $ {{ number_format($this->summary['sales'], 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Pedidos no cancelados</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-zinc-500">Pedidos registrados</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900">
                    {{ number_format($this->summary['orders'], 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">En el periodo seleccionado</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-zinc-500">Ticket promedio</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900">
                    $ {{ number_format($this->summary['average'], 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Venta promedio por pedido</p>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-zinc-500">Unidades vendidas</p>
                <p class="mt-2 text-2xl font-bold text-zinc-900">
                    {{ number_format($this->summary['units'], 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">Productos en pedidos no cancelados</p>
            </div>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-zinc-900">Ventas por día</h2>

            @if ($this->dailySales->isEmpty())
                <p class="py-6 text-center text-sm text-zinc-500">
                    No hay ventas registradas en este periodo.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-zinc-200 bg-zinc-50 text-zinc-600">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 text-center font-semibold">Pedidos</th>
                                <th class="px-4 py-3 text-right font-semibold">Ventas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($this->dailySales as $day)
                                <tr>
                                    <td class="px-4 py-3 text-zinc-700">
                                        {{ \Carbon\Carbon::parse($day->day)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-zinc-700">
                                        {{ $day->orders_count }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-zinc-900">
                                        $ {{ number_format($day->sales, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-zinc-900">Productos más vendidos</h2>

                @if ($this->topProducts->isEmpty())
                    <p class="py-6 text-center text-sm text-zinc-500">
                        No hay productos vendidos en este periodo.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-zinc-600">
                                <tr>
                                    <th class="px-3 py-3 font-semibold">Producto</th>
                                    <th class="px-3 py-3 text-center font-semibold">Unidades</th>
                                    <th class="px-3 py-3 text-right font-semibold">Ventas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($this->topProducts as $product)
                                    <tr>
                                        <td class="px-3 py-3 font-medium text-zinc-800">
                                            {{ $product->name }}
                                        </td>
                                        <td class="px-3 py-3 text-center text-zinc-700">
                                            {{ $product->units }}
                                        </td>
                                        <td class="px-3 py-3 text-right text-zinc-800">
                                            $ {{ number_format($product->sales, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-zinc-900">Ventas por establecimiento</h2>

                @if ($this->topEstablishments->isEmpty())
                    <p class="py-6 text-center text-sm text-zinc-500">
                        No hay ventas registradas en este periodo.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 bg-zinc-50 text-zinc-600">
                                <tr>
                                    <th class="px-3 py-3 font-semibold">Establecimiento</th>
                                    <th class="px-3 py-3 text-center font-semibold">Pedidos</th>
                                    <th class="px-3 py-3 text-right font-semibold">Ventas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($this->topEstablishments as $establishment)
                                    <tr>
                                        <td class="px-3 py-3 font-medium text-zinc-800">
                                            {{ $establishment->name }}
                                        </td>
                                        <td class="px-3 py-3 text-center text-zinc-700">
                                            {{ $establishment->orders_count }}
                                        </td>
                                        <td class="px-3 py-3 text-right text-zinc-800">
                                            $ {{ number_format($establishment->sales, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>


