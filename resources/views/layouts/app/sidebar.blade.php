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
        $area = $user->isPlatformAdmin() ? 'Administración de la plataforma' : ($user->isEstablishmentUser() ? 'Gestión del negocio' : 'Mi cuenta');
    @endphp

    <body class="min-h-screen bg-slate-50 text-slate-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-slate-200 bg-white">
            <flux:sidebar.header class="px-4 pt-5 pb-3">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                    <x-nyva-logo />
                    <span class="leading-tight">
                        <span class="block text-base font-bold tracking-tight text-slate-800">NYVA</span>
                        <span class="block text-xs text-slate-500">{{ $area }}</span>
                    </span>
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="px-3">
                @if ($user->isPlatformAdmin())
                    <flux:sidebar.group heading="Plataforma" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>Dashboard</flux:sidebar.item>
                        <flux:sidebar.item icon="building-storefront" :href="route('admin.establishments.index')" :current="request()->routeIs('admin.establishments.*')" wire:navigate>Establecimientos</flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>Usuarios</flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group heading="Negocio SaaS" class="grid">
                        <flux:sidebar.item icon="rectangle-stack" :href="route('admin.plans.index')" :current="request()->routeIs('admin.plans.*')" wire:navigate>Planes</flux:sidebar.item>
                        <flux:sidebar.item icon="credit-card" :href="route('admin.subscriptions.index')" :current="request()->routeIs('admin.subscriptions.*')" wire:navigate>Suscripciones</flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('admin.statistics')" :current="request()->routeIs('admin.statistics')" wire:navigate>Estadísticas</flux:sidebar.item>
                    </flux:sidebar.group>
                @elseif ($user->isEstablishmentUser())
                    <flux:sidebar.group heading="Principal" class="grid">
                        <flux:sidebar.item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Inicio</flux:sidebar.item>
                        <flux:sidebar.item icon="building-storefront" :href="route('establishments.index')" :current="request()->routeIs('establishments.*')" wire:navigate>Mi establecimiento</flux:sidebar.item>
                    </flux:sidebar.group>

                    @if ($canCatalog)
                        <flux:sidebar.group heading="Catálogo" class="grid">
                            <flux:sidebar.item icon="archive-box" :href="route('categories.index')" :current="request()->routeIs('categories.*')" wire:navigate>Categorías</flux:sidebar.item>
                            <flux:sidebar.item icon="shopping-bag" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>Productos</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif

                    @if ($canOrders)
                        <flux:sidebar.group heading="Ventas" class="grid">
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>Pedidos</flux:sidebar.item>
                            <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>Reportes</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif

                    @if ($canSettings)
                        <flux:sidebar.group heading="Configuración" class="grid">
                            <flux:sidebar.item icon="clock" :href="route('schedules.index')" :current="request()->routeIs('schedules.*')" wire:navigate>Horarios</flux:sidebar.item>
                            <flux:sidebar.item icon="question-mark-circle" :href="route('faqs.index')" :current="request()->routeIs('faqs.*')" wire:navigate>Preguntas frecuentes</flux:sidebar.item>
                            <flux:sidebar.item icon="chat-bubble-left-right" :href="route('chatbot.settings')" :current="request()->routeIs('chatbot.*')" wire:navigate>Chatbot</flux:sidebar.item>
                            <flux:sidebar.item icon="chat-bubble-left-ellipsis" :href="route('conversations.index')" :current="request()->routeIs('conversations.*')" wire:navigate>Conversaciones</flux:sidebar.item>
                            <flux:sidebar.item icon="user-group" :href="route('team.index')" :current="request()->routeIs('team.*')" wire:navigate>Equipo</flux:sidebar.item>
                        </flux:sidebar.group>
                    @endif
                @else
                    <flux:sidebar.group heading="Cliente" class="grid">
                        <flux:sidebar.item icon="clipboard-document-list" :href="route('my-orders.index')" :current="request()->routeIs('my-orders.*')" wire:navigate>Mis pedidos</flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                <flux:sidebar.group heading="Cuenta" class="grid">
                    <flux:sidebar.item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>Mi perfil</flux:sidebar.item>
                    <flux:sidebar.item icon="shield-check" :href="route('security.edit')" :current="request()->routeIs('security.edit')" wire:navigate>Seguridad</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>



            <flux:spacer />

            <form method="POST" action="{{ route('logout') }}" class="px-3 pb-4">
                @csrf
                <flux:sidebar.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">
                    Cerrar sesión
                </flux:sidebar.item>
            </form>
        </flux:sidebar>

        <flux:header sticky class="border-b border-slate-200 bg-white/90 backdrop-blur">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" aria-label="Abrir menú" />

            <div class="min-w-0 lg:ps-2">
                <p class="truncate font-semibold text-slate-800">{{ $title ?? 'NYVA' }}</p>
                <p class="hidden truncate text-xs text-slate-500 sm:block">{{ $area }}</p>
            </div>

            <flux:spacer />

            <livewire:pages::notifications.bell />

            <flux:dropdown position="bottom" align="end">
                <button type="button" class="flex items-center gap-2 rounded-lg p-1.5 text-start hover:bg-slate-100" aria-label="Menú de usuario">
                    <flux:avatar size="sm" :name="$fullName" :initials="$user->initials()" class="bg-blue-100! text-blue-800!" />
                    <span class="hidden max-w-40 truncate text-sm font-medium text-slate-700 md:block">{{ $fullName }}</span>
                    <flux:icon name="chevron-down" class="hidden size-4 text-slate-400 md:block" />
                </button>

                <flux:menu>
                    <div class="px-2 py-1.5 text-sm">
                        <p class="truncate font-semibold text-slate-800">{{ $fullName }}</p>
                        <p class="truncate text-slate-500">{{ $user->email }}</p>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>Mi perfil</flux:menu.item>
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

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
