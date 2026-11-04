<?php

use App\Http\Controllers\DashboardController;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Administración')] class extends Component {
    public function with(): array
    {
        $today = now()->toDateString();
        $soon = now()->addDays(7)->toDateString();

        $activeSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $today)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today));

        return [
            'establishments' => Establishment::count(),
            'activeEstablishments' => Establishment::where('status', 'active')->count(),
            'suspended' => Establishment::where('status', 'suspended')->count(),
            'users' => User::count(),
            'activeSubscriptions' => (clone $activeSubscriptions)->count(),
            'expiring' => (clone $activeSubscriptions)
                ->with(['establishment', 'plan'])
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<=', $soon)
                ->orderBy('end_date')
                ->get(),
            'orders' => Order::count(),
            'sales' => Order::where('status', '!=', 'cancelled')->sum('total'),
            'salesChart' => DashboardController::salesByDay(Order::query()),
            'latest' => Establishment::query()->orderByDesc('registered_at')->limit(5)->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Panel de plataforma" subtitle="Supervisión global de NYVA." />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Establecimientos" :value="$establishments" :hint="$activeEstablishments.' activos · '.$suspended.' suspendidos'" icon="building-storefront" />
        <x-stat-card label="Suscripciones vigentes" :value="$activeSubscriptions" :hint="$expiring->count().' por vencer en 7 días'" tone="green" icon="credit-card" />
        <x-stat-card label="Usuarios" :value="$users" hint="Cuentas registradas" tone="violet" icon="users" />
        <x-stat-card label="Pedidos" :value="$orders" :hint="'$ '.number_format((float) $sales, 0, ',', '.').' en ventas'" tone="amber" icon="clipboard-document-list" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="nyva-card p-5">
            <h2 class="mb-4 font-semibold">Ventas de la plataforma, últimos 7 días</h2>
            <x-bar-chart :points="$salesChart" />
        </section>

        <section class="nyva-card p-5">
            <h2 class="mb-4 font-semibold">Suscripciones por vencer</h2>

            @forelse ($expiring as $subscription)
                <div wire:key="expiring-{{ $subscription->id }}" class="flex items-center justify-between gap-3 border-b border-zinc-100 py-2 text-sm last:border-0 dark:border-zinc-800">
                    <span>{{ $subscription->establishment?->name }} · {{ $subscription->plan?->name }}</span>
                    <span class="whitespace-nowrap text-zinc-500">{{ $subscription->end_date->format('d/m/Y') }}</span>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-500">No hay suscripciones por vencer.</p>
            @endforelse
        </section>
    </div>

    <section class="nyva-card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Últimos establecimientos</h2>
            <a href="{{ route('admin.establishments') }}" wire:navigate class="nyva-link text-sm">Ver todos</a>
        </div>

        @forelse ($latest as $establishment)
            <div wire:key="latest-{{ $establishment->id }}" class="flex items-center justify-between gap-3 border-b border-zinc-100 py-2 text-sm last:border-0 dark:border-zinc-800">
                <span>{{ $establishment->name }}</span>
                <span class="text-zinc-500">{{ Establishment::STATUSES[$establishment->status] ?? $establishment->status }}</span>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-zinc-500">Todavía no hay establecimientos.</p>
        @endforelse
    </section>
</div>
