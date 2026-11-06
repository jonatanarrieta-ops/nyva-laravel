@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Productos" subtitle="Administra los productos de tus establecimientos.">
            <x-ui.button :href="route('products.create')" icon="plus">Nuevo producto</x-ui.button>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <form method="GET" action="{{ route('products.index') }}" class="nyva-card grid grid-cols-1 items-end gap-3 p-4 md:grid-cols-[1fr_1fr_1fr_auto]">
            <x-ui.input label="Buscar" name="search" type="search" :value="$search" placeholder="Nombre o descripción..." />

            <x-ui.select label="Establecimiento" name="establishment">
                <option value="">Todos los establecimientos</option>
                @foreach ($establishments as $establishment)
                    <option value="{{ $establishment->id }}" @selected($establishmentId === (string) $establishment->id)>{{ $establishment->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Categoría" name="category">
                <option value="">Todas las categorías</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary">Filtrar</x-ui.button>
        </form>

        @if ($products->isEmpty())
            <div class="nyva-card">
                <x-ui.empty-state icon="shopping-bag" title="No se encontraron productos" description="Crea un producto o cambia los filtros." />
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($products as $product)
                    <article class="nyva-card flex flex-col overflow-hidden">
                        <div class="relative flex h-36 items-center justify-center bg-slate-100 text-3xl font-semibold text-slate-300">
                            @if ($product->imageUrl())
                                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 size-full object-cover">
                            @endif
                            <span aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold text-slate-800">{{ $product->name }}</h3>
                                    <p class="truncate text-xs text-slate-500">{{ $product->category->name }} · {{ $product->category->establishment->name }}</p>
                                </div>
                                <p class="whitespace-nowrap font-bold text-slate-800">$ {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                            </div>

                            @if ($product->description)
                                <p class="line-clamp-2 text-sm text-slate-500">{{ $product->description }}</p>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge :tone="$product->available ? 'green' : 'slate'">{{ $product->available ? 'Disponible' : 'No disponible' }}</x-ui.badge>
                                <x-ui.badge :tone="$product->status === 'active' ? 'blue' : 'slate'">{{ $product->status === 'active' ? 'Activo' : 'Inactivo' }}</x-ui.badge>
                            </div>

                            <div class="mt-auto flex justify-end gap-4 border-t border-slate-100 pt-3 text-sm">
                                <a href="{{ route('products.edit', $product->id) }}" class="nyva-link">Editar</a>
                                <form method="POST" action="{{ route('products.destroy', $product->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('¿Seguro que deseas eliminar este producto?')" class="nyva-link-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div>{{ $products->links() }}</div>
    </div>
@endsection
