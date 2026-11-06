@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Planes" subtitle="Define precios, límites y funcionalidades.">
            <a href="{{ route('admin.plans.create') }}" class="nyva-btn">Nuevo plan</a>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($plans as $plan)
                <article class="nyva-card flex flex-col gap-3 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="text-lg font-semibold">{{ $plan->name }}</h2>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium dark:bg-zinc-800">{{ $plan->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                    </div>

                    <p class="text-2xl font-bold">$ {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}<span class="text-sm font-normal text-zinc-500"> / mes</span></p>

                    @if ($plan->description)
                        <p class="text-sm text-zinc-500">{{ $plan->description }}</p>
                    @endif

                    <ul class="flex flex-col gap-1 text-sm">
                        <li>{{ $plan->duration_days }} días de duración</li>
                        <li>{{ $plan->product_limit ?? 'Sin límite de' }} productos</li>
                        <li>{{ $plan->user_limit ?? 'Sin límite de' }} usuarios</li>
                        @foreach ($plan->features ?? [] as $feature)
                            <li>{{ \App\Models\Plan::FEATURES[$feature] ?? $feature }}</li>
                        @endforeach
                    </ul>

                    <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 text-sm dark:border-zinc-800">
                        <span class="text-zinc-500">{{ $plan->subscriptions_count }} suscripciones</span>
                        <span class="flex gap-3">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="nyva-link">Editar</a>
                            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('¿Eliminar este plan?')" class="nyva-link-danger">Eliminar</button>
                            </form>
                        </span>
                    </div>
                </article>
            @empty
                <p class="py-10 text-center text-sm text-zinc-500 md:col-span-2 xl:col-span-3">Todavía no hay planes.</p>
            @endforelse
        </div>
    </div>
@endsection
