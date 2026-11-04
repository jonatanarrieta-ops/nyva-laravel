<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    @php
        $user = auth()->user();
        $fullName = trim($user->first_name.' '.$user->last_name);
        $canCatalog = $user->isEstablishmentUser() && $user->accessibleEstablishments('catalog')->exists();
        $canOrders = $user->isEstablishmentUser() && $user->accessibleEstablishments('orders')->exists();
        $canSettings = $user->isEstablishmentUser() && $user->accessibleEstablishments('settings')->exists();
    @endphp

    <body class="min-h-screen bg-zinc-50 text-zinc-950 dark:bg-zinc-950 dark:text-white">
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900"
        >
            <flux:sidebar.header class="px-4 py-4">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 font-semibold">
                    <x-nyva-logo />
                    <span>
                        NYVA
                        <span class="block text-xs font-normal text-zinc-500">
                            {{ $user->isPlatformAdmin() ? 'Administración' : ($user->isEstablishmentUser() ? 'Mi negocio' : 'Cliente') }}
                        </span>
                    </span>
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="px-3">
                @if ($user->isPlatformAdmin())
                    <flux:sidebar.group heading="PLATAFORMA" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>Dashboard</flux:sidebar.item>
                        <flux:sidebar.item icon="building-storefront" :href="route('admin.establishments')" :current="request()->routeIs('admin.establishments')" wire:navigate>Establecimientos</flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>Usuarios</flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="NEGOCIO SAAS" class="grid">
                        <flux:sidebar.item icon="rectangle-stack" :href="route('admin.plans')" :current="request()->routeIs('admin.plans')" wire:navigate>Planes</flux:sidebar.item>
                        <flux:sidebar.item icon="credit-card" :href="route('admin.subscriptions')" :current="request()->routeIs('admin.subscriptions')" wire:navigate>Suscripciones</flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('admin.statistics')" :current="request()->routeIs('admin.statistics')" wire:navigate>Estadísticas</flux:sidebar.item>
                    </flux:sidebar.group>
                @elseif ($user->isEstablishmentUser())
                    <flux:sidebar.group heading="PRINCIPAL" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Inicio</flux:sidebar.item>
                        <flux:sidebar.item icon="building-storefront" :href="route('establishments.index')" :current="request()->routeIs('establishments.*')" wire:navigate>Mi establecimiento</flux:sidebar.item>
                    </flux:sidebar.group>

                    @if ($canCatalog)
                        <flux:sidebar.group heading="CATÁLOGO" class="grid">
                            <flux:sidebar.item icon="archive-box" :href="route('categories.index')" :current="request()->routeIs('categories.*')" wire:navigate>Categorías</flux:sidebar.item>
                            <flux:sidebar.item icon="shopping-bag" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>Productos</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif

                    @if ($canOrders)
                        <flux:sidebar.group heading="VENTAS" class="grid">
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>Pedidos</flux:sidebar.item>
                            <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>Reportes</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif

                    @if ($canSettings)
                        <flux:sidebar.group heading="CONFIGURACIÓN" class="grid">
                            <flux:sidebar.item icon="clock" :href="route('schedules.index')" :current="request()->routeIs('schedules.*')" wire:navigate>Horarios</flux:sidebar.item>
                            <flux:sidebar.item icon="question-mark-circle" :href="route('faqs.index')" :current="request()->routeIs('faqs.*')" wire:navigate>Preguntas frecuentes</flux:sidebar.item>
                            <flux:sidebar.item icon="chat-bubble-left-right" :href="route('chatbot.settings')" :current="request()->routeIs('chatbot.*')" wire:navigate>Chatbot</flux:sidebar.item>
                            <flux:sidebar.item icon="user-group" :href="route('team.index')" :current="request()->routeIs('team.*')" wire:navigate>Equipo</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @else
                    <flux:sidebar.group heading="CLIENTE" class="grid">
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('my-orders.index')" :current="request()->routeIs('my-orders.*')" wire:navigate>Mis pedidos</flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                <flux:sidebar.group heading="CUENTA" class="grid">
                    <flux:sidebar.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>Mi perfil</flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('appearance.edit')" :current="request()->routeIs('appearance.edit')" wire:navigate>Apariencia</flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>Seguridad</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="$fullName" />
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <livewire:pages::notifications.bell />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="$user->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start">
                                <flux:avatar :name="$fullName" />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $fullName }}</flux:heading>
                                    <flux:text class="truncate">{{ $user->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>Mi perfil</flux:menu.item>
                    <flux:menu.item :href="route('appearance.edit')" icon="adjustments-horizontal" wire:navigate>Apariencia</flux:menu.item>
                    <flux:menu.item :href="route('security.edit')" icon="shield-check" wire:navigate>Seguridad</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf

                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Cerrar sesión
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <div class="fixed end-4 top-3 z-40 hidden lg:block">
            <livewire:pages::notifications.bell />
        </div>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
