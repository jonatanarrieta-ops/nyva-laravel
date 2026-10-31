<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-gray-50 text-zinc-900">
        <header class="border-b border-zinc-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                    <span class="flex size-8 items-center justify-center rounded-md bg-indigo-700 text-white">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </span>
                    {{ config('app.name') }}
                </a>

                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium text-zinc-600">
                    <a href="{{ route('home') }}" wire:navigate class="hover:text-zinc-900">Catálogo</a>

                    <livewire:pages::cart.badge />

                    @auth
                        <a href="{{ route('my-orders.index') }}" wire:navigate class="hover:text-zinc-900">Mis pedidos</a>

                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-zinc-900">Panel</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="cursor-pointer hover:text-zinc-900">Cerrar sesión</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-zinc-900">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-white hover:bg-indigo-700">Registrarse</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
