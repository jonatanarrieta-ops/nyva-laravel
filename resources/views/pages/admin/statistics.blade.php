<?php

use App\Http\Controllers\DashboardController;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Plan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Estadísticas')] class extends Component {
    public function with(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $colors = [
            'pending' => 'bg-amber-500',
            'confirmed' => 'bg-blue-500',
            'preparing' => 'bg-indigo-500',
            'shipped' => 'bg-violet-500',
            'delivered' => 'bg-green-500',
            'cancelled' => 'bg-red-500',
        ];

        return [
            'salesChart' => DashboardController::salesByDay(Order::query(), 14),
            'statusItems' => collect(Order::STATUS_LABELS)
                ->map(fn ($label, $status) => [
                    'label' => $label,
                    'value' => (int) $counts->get($status, 0),
                    'color' => $colors[$status],
                ])
                ->values()
                ->all(),
            'topEstablishments' => Establishment::query()
                ->withCount('orders')
                ->withSum(['orders as sales' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'total')
                ->orderByDesc('orders_count')
                ->limit(10)
                ->get(),
            'planItems' => Plan::query()
                ->withCount(['subscriptions' => fn ($query) => $query->where('status', 'active')])
                ->get()
                ->map(fn (Plan $plan) => ['label' => $plan->name, 'value' => $plan->subscriptions_count, 'color' => 'bg-indigo-500'])
                ->all(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Estadísticas" subtitle="Comportamiento general de la plataforma." />

    <section class="nyva-card p-5">
        <h2 class="mb-4 font-semibold">Ventas de los últimos 14 días</h2>
        <x-bar-chart :points="$salesChart" />
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="nyva-card p-5">
            <h2 class="mb-4 font-semibold">Pedidos por estado</h2>
            <x-status-bars :items="$statusItems" />
        </section>

        <section class="nyva-card p-5">
            <h2 class="mb-4 font-semibold">Suscripciones activas por plan</h2>

            @if ($planItems === [])
                <p class="py-6 text-center text-sm text-zinc-500">Todavía no hay planes.</p>
            @else
                <x-status-bars :items="$planItems" />
            @endif
        </section>
    </div>

    <section class="nyva-card overflow-hidden">
        <h2 class="p-5 pb-3 font-semibold">Establecimientos con más actividad</h2>

        <div class="overflow-x-auto">
            <table class="nyva-table">
                <thead><tr><th>Establecimiento</th><th>Pedidos</th><th class="text-right">Ventas</th></tr></thead>
                <tbody>
                    @forelse ($topEstablishments as $establishment)
                        <tr wire:key="top-{{ $establishment->id }}">
                            <td class="font-medium">{{ $establishment->name }}</td>
                            <td>{{ $establishment->orders_count }}</td>
                            <td class="text-right">$ {{ number_format((float) $establishment->sales, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-10 text-center text-zinc-500">Todavía no hay datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
