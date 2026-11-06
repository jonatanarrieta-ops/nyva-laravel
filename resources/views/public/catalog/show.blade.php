@extends('layouts.public')

@section('content')
    <div class="flex flex-col gap-6">
        <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
            <div class="h-32 bg-gradient-to-r from-blue-800 to-blue-500 sm:h-44">
                @if ($establishment->coverUrl())
                    <img src="{{ $establishment->coverUrl() }}" alt="" class="h-full w-full object-cover">
                @endif
            </div>

            <div class="p-5 sm:p-6">
                <div class="-mt-14 flex flex-wrap items-end justify-between gap-3 sm:-mt-16">
                    <span class="flex size-20 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-blue-100 text-2xl font-bold text-blue-800 shadow-sm sm:size-24">
                        @if ($establishment->logoUrl())
                            <img src="{{ $establishment->logoUrl() }}" alt="Logotipo de {{ $establishment->name }}" class="h-full w-full object-cover">
                        @else
                            {{ mb_substr($establishment->name, 0, 1) }}
                        @endif
                    </span>

                    <span @class([
                        'rounded-full px-3 py-1 text-xs font-semibold',
                        'bg-green-100 text-green-800' => $canOrder,
                        'bg-zinc-200 text-zinc-700' => ! $canOrder,
                    ])>
                        {{ $canOrder ? 'Recibiendo pedidos' : 'Sin pedidos por ahora' }}
                    </span>
                </div>

                <p class="mt-3 text-xs font-medium uppercase tracking-wide text-blue-800">{{ $establishment->type }}</p>
                <h1 class="mt-1 text-2xl font-bold text-zinc-900 sm:text-3xl">{{ $establishment->name }}</h1>

                @if ($establishment->description)
                    <p class="mt-2 text-sm text-zinc-600">{{ $establishment->description }}</p>
                @endif

                <dl class="mt-4 grid gap-4 text-sm text-zinc-600 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="font-medium text-zinc-900">Dirección</dt>
                        <dd class="break-words">{{ $establishment->address }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-900">Teléfono</dt>
                        <dd>{{ $establishment->phone }}</dd>
                    </div>
                    @if ($establishment->email)
                        <div>
                            <dt class="font-medium text-zinc-900">Correo</dt>
                            <dd class="break-words">{{ $establishment->email }}</dd>
                        </div>
                    @endif
                    @if ($hours->isNotEmpty())
                        <div>
                            <dt class="font-medium text-zinc-900">Horarios</dt>
                            <dd>
                                @foreach ($hours as $line)
                                    <span class="block">{{ $line }}</span>
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </section>
        @if (session('notice'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('notice') }}
                <a href="{{ route('cart.index') }}" class="font-semibold underline">Ver carrito</a>
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        @if ($pendingProduct)
            <div role="alertdialog" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">Tu carrito contiene productos de otro establecimiento.</p>
                <p class="mt-1">¿Deseas vaciarlo y comenzar un nuevo pedido?</p>

                <div class="mt-3 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('cart.items.store', $pendingProduct) }}">
                        @csrf
                        <input type="hidden" name="replace" value="1">
                        <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2 font-semibold text-white hover:bg-blue-800">
                            Vaciar y agregar
                        </button>
                    </form>
                    <a href="{{ route('catalog.show', $establishment) }}" class="rounded-lg border border-amber-300 px-4 py-2 font-semibold hover:bg-amber-100">
                        Conservar mi carrito
                    </a>
                </div>
            </div>
        @endif

        @forelse ($categories as $category)
            <section>
                <h2 class="mb-3 text-lg font-semibold text-zinc-900">{{ $category->name }}</h2>

                @if ($category->description)
                    <p class="-mt-2 mb-3 text-sm text-zinc-500">{{ $category->description }}</p>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->products as $product)
                        <article
                            @class([
                                'flex min-w-0 flex-col overflow-hidden rounded-xl border bg-white shadow-sm',
                                'border-zinc-200' => $product->available,
                                'border-zinc-200 bg-zinc-50' => ! $product->available,
                            ])
                        >
                            <div class="flex h-36 items-center justify-center bg-zinc-100 text-3xl font-semibold text-zinc-300">
                                @if ($product->imageUrl())
                                    <img
                                        src="{{ $product->imageUrl() }}"
                                        alt="{{ $product->name }}"
                                        loading="lazy"
                                        onerror="this.remove()"
                                        @class(['h-full w-full object-cover', 'opacity-50 grayscale' => ! $product->available])
                                    >
                                @else
                                    <span aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
                                @endif
                            </div>

                            <div class="flex flex-1 flex-col gap-2 p-4">
                                <h3 class="font-semibold text-zinc-900">{{ $product->name }}</h3>

                                @if ($product->description)
                                    <p class="text-sm text-zinc-500">{{ $product->description }}</p>
                                @endif

                                <div class="mt-auto flex items-center justify-between gap-3 pt-2">
                                    <span class="font-bold text-zinc-900">$ {{ number_format((float) $product->price, 0, ',', '.') }}</span>

                                    @if ($product->available && $canOrder)
                                        <form method="POST" action="{{ route('cart.items.store', $product) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                                                Agregar
                                            </button>
                                        </form>
                                    @elseif ($product->available)
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-600">Disponible</span>
                                    @else
                                        <span class="rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700">No disponible</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">
                Este establecimiento todavía no tiene productos publicados.
            </div>
        @endforelse

        <livewire:pages::chatbot.widget :establishment-id="$establishment->id" />
    </div>
@endsection
