@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Establecimientos" subtitle="Registra, edita y controla los negocios de la plataforma.">
            <a href="{{ route('admin.establishments.create') }}" class="nyva-btn">Nuevo establecimiento</a>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        <section class="nyva-card overflow-hidden">
            <form method="GET" action="{{ route('admin.establishments.index') }}" class="flex flex-col gap-3 p-4 sm:flex-row">
                <input type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre..." aria-label="Buscar" class="nyva-input sm:max-w-xs">
                <select name="status" aria-label="Filtrar por estado" onchange="this.form.submit()" class="nyva-input sm:w-52">
                    <option value="">Todos los estados</option>
                    @foreach (\App\Models\Establishment::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="nyva-btn-secondary">Filtrar</button>
            </form>

            <div class="overflow-x-auto">
                <table class="nyva-table">
                    <thead>
                        <tr>
                            <th>Establecimiento</th>
                            <th>Propietario</th>
                            <th>Estado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($establishments as $establishment)
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $establishment->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $establishment->type }}</div>
                                </td>
                                <td>{{ $establishment->user?->email }}</td>
                                <td>{{ \App\Models\Establishment::STATUSES[$establishment->status] ?? $establishment->status }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                        <a href="{{ route('admin.establishments.edit', $establishment) }}" class="nyva-link">Editar</a>

                                        <form method="POST" action="{{ route('admin.establishments.status', $establishment) }}">
                                            @csrf
                                            @method('PATCH')
                                            @if (in_array($establishment->status, ['suspended', 'inactive'], true))
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="nyva-link">Activar</button>
                                            @else
                                                <input type="hidden" name="status" value="suspended">
                                                <button type="submit" onclick="return confirm('¿Suspender este establecimiento? Dejará de recibir pedidos.')" class="nyva-link-danger">Suspender</button>
                                            @endif
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-zinc-500">No se encontraron establecimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4">{{ $establishments->links() }}</div>
        </section>
    </div>
@endsection
