@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Suscripciones" subtitle="Asigna planes y controla sus vencimientos.">
            <a href="{{ route('admin.subscriptions.create') }}" class="nyva-btn">Nueva suscripción</a>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        <section class="nyva-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="nyva-table">
                    <thead>
                        <tr><th>Establecimiento</th><th>Plan</th><th>Periodo</th><th>Vencimiento</th><th>Estado</th><th>Uso</th><th class="text-right">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($subscriptions as $subscription)
                            @php
                                $expired = $subscription->end_date?->endOfDay()->isPast();
                                $plan = $subscription->plan;
                            @endphp
                            <tr>
                                <td class="font-medium">{{ $subscription->establishment?->name }}</td>
                                <td>{{ $plan?->name }}</td>
                                <td>{{ $subscription->billing_period === 'annual' ? 'Anual' : 'Mensual' }}</td>
                                <td>{{ $subscription->end_date?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                                <td>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-red-100 text-red-800' => $expired,
                                        'bg-green-100 text-green-800' => ! $expired && $subscription->status === 'active',
                                        'bg-zinc-100 text-zinc-700' => ! $expired && $subscription->status !== 'active',
                                    ])>
                                        {{ $expired ? 'Vencida' : ($subscription->status === 'active' ? 'Activa' : 'Inactiva') }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ $subscription->establishment?->productCount() }} / {{ $plan?->product_limit ?? '∞' }} productos
                                </td>
                                <td class="text-right"><a href="{{ route('admin.subscriptions.edit', $subscription) }}" class="nyva-link">Editar</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-10 text-center text-zinc-500">Todavía no hay suscripciones.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">{{ $subscriptions->links() }}</div>
        </section>
    </div>
@endsection
