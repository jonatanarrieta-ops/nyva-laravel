<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    <body class="nyva-public min-h-screen bg-gray-50 text-zinc-900">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 sm:px-6">
                @isset($publicEstablishment)
                    {{-- Cada página pública lleva solo a su propio negocio. --}}
                    <a href="{{ route('catalog.show', $publicEstablishment) }}" wire:navigate class="flex min-w-0 items-center gap-2 font-semibold">
                        <span class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-blue-100 text-sm font-bold text-blue-800">
                            @if ($publicEstablishment->logoUrl())
                                <img src="{{ $publicEstablishment->logoUrl() }}" alt="" class="size-full object-cover">
                            @else
                                {{ mb_substr($publicEstablishment->name, 0, 1) }}
                            @endif
                        </span>
                        <span class="truncate">{{ $publicEstablishment->name }}</span>
                    </a>
                @else
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                        <x-nyva-logo class="size-8" />
                        {{ config('app.name') }}
                    </a>
                @endisset

                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium text-zinc-600">
                    <livewire:pages::cart.badge />

                    {{-- La página pública es solo para clientes: no enlaza a cuentas ni paneles de la plataforma. --}}
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
