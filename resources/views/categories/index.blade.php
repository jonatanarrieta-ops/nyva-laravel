@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Categorías" subtitle="Organiza los productos de tus establecimientos.">
            <x-ui.button :href="route('categories.create')" icon="plus">Nueva categoría</x-ui.button>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <section class="nyva-card overflow-hidden">
            <form method="GET" action="{{ route('categories.index') }}" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-[1fr_16rem_auto]">
                <x-ui.input name="search" type="search" :value="$search" placeholder="Buscar categorías..." aria-label="Buscar categorías" />

                <x-ui.select name="establishment" aria-label="Filtrar por establecimiento">
                    <option value="">Todos los establecimientos</option>
                    @foreach ($establishments as $establishment)
                        <option value="{{ $establishment->id }}" @selected($establishmentId === (string) $establishment->id)>{{ $establishment->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
            </form>

            @if ($categories->isEmpty())
                <x-ui.empty-state
                    icon="archive-box"
                    :title="$search !== '' || $establishmentId !== '' ? 'No se encontraron categorías' : 'Todavía no tienes categorías'"
                    :description="$search !== '' || $establishmentId !== '' ? 'Prueba con otra búsqueda o cambia el filtro.' : 'Crea tu primera categoría para organizar tus productos.'"
                >
                    @if ($search === '' && $establishmentId === '')
                        <x-ui.button :href="route('categories.create')" icon="plus">Crear primera categoría</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <div class="hidden overflow-x-auto md:block">
                    <table class="nyva-table">
                        <thead>
                            <tr><th>Categoría</th><th>Establecimiento</th><th>Productos</th><th>Estado</th><th class="text-right">Acciones</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>
                                        <div class="font-semibold text-slate-800">{{ $category->name }}</div>
                                        @if ($category->description)
                                            <div class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $category->description }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $category->establishment?->name ?? 'Sin establecimiento' }}</td>
                                    <td><x-ui.badge>{{ $category->products_count }}</x-ui.badge></td>
                                    <td><x-ui.badge :tone="$category->status === 'active' ? 'green' : 'slate'">{{ $category->status === 'active' ? 'Activa' : 'Inactiva' }}</x-ui.badge></td>
                                    <td>
                                        <div class="flex justify-end gap-4 whitespace-nowrap">
                                            <a href="{{ route('categories.edit', $category->id) }}" class="nyva-link">Editar</a>
                                            <form method="POST" action="{{ route('categories.destroy', $category->id) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?')" class="nyva-link-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-100 md:hidden">
                    @foreach ($categories as $category)
                        <div class="flex flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800">{{ $category->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $category->establishment?->name }} · {{ $category->products_count }} productos</p>
                                </div>
                                <x-ui.badge :tone="$category->status === 'active' ? 'green' : 'slate'">{{ $category->status === 'active' ? 'Activa' : 'Inactiva' }}</x-ui.badge>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <a href="{{ route('categories.edit', $category->id) }}" class="nyva-link">Editar</a>
                                <form method="POST" action="{{ route('categories.destroy', $category->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('¿Seguro que deseas eliminar esta categoría?')" class="nyva-link-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 p-4">{{ $categories->links() }}</div>
            @endif
        </section>
    </div>
@endsection
