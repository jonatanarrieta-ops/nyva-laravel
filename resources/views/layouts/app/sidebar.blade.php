
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>

    <body class="min-h-screen bg-white text-zinc-950 dark:bg-zinc-950 dark:text-white">
        <flux:sidebar
            sticky
            collapsible="mobile"
            class="border-e border-zinc-200/80 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900"
        >
            {{-- Marca --}}
            <flux:sidebar.header class="px-4 py-4">
                <x-app-logo
                    :sidebar="true"
                    href="{{ route('dashboard') }}"
                    wire:navigate
                />

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            {{-- NavegaciÃƒÂ³n principal --}}
            <flux:sidebar.nav class="px-3">
                <flux:sidebar.group heading="PRINCIPAL" class="grid">
                    <flux:sidebar.item
                        icon="squares-2x2"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        Dashboard
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group
                    heading="GESTIÃƒâ€œN DEL NEGOCIO"
                    class="grid"
                    expandable
                    expanded
                >
                    <flux:sidebar.item
                        icon="building-office-2"
                        :href="route('establishments.index')"
                        :current="request()->routeIs('establishments.*')"
                        wire:navigate
                    >
                        Establecimientos
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="archive-box"
                        :href="route('categories.index')"
                        :current="request()->routeIs('categories.*')"
                        wire:navigate
                    >
                        CategorÃ­as
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="shopping-bag"
                        :href="route('products.index')"
                        :current="request()->routeIs('products.*')"
                        wire:navigate
                    >
                        Productos
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="shopping-bag"
                        :href="route('orders.index')"
                        :current="request()->routeIs('orders.*')"
                        wire:navigate
                    >
                        Pedidos
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="chart-bar"
                        :href="route('reports.index')"
                        :current="request()->routeIs('reports.*')"
                        wire:navigate
                    >
                        Reportes
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="CUENTA" class="grid">
                    <flux:sidebar.item
                        icon="user"
                        :href="route('profile.edit')"
                        :current="request()->routeIs('profile.edit')"
                        wire:navigate
                    >
                        Mi perfil
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="cog-6-tooth"
                        :href="route('appearance.edit')"
                        :current="request()->routeIs('appearance.edit')"
                        wire:navigate
                    >
                        Apariencia
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="shield-check"
                        :href="route('security.edit')"
                        :current="request()->routeIs('security.edit')"
                        wire:navigate
                    >
                        Seguridad
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Recursos --}}
            <flux:sidebar.nav class="px-3">
                <flux:sidebar.group heading="RECURSOS" class="grid">
                    <flux:sidebar.item
                        icon="book-open"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                    >
                        DocumentaciÃƒÂ³n
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            {{-- Usuario de escritorio --}}
            <x-desktop-user-menu
                class="hidden lg:block"
                :name="trim(auth()->user()->first_name . ' ' . auth()->user()->last_name)"
            />
        </flux:sidebar>

        {{-- Cabecera mÃƒÂ³vil --}}
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle
                class="lg:hidden"
                icon="bars-2"
                inset="left"
            />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start">
                                <flux:avatar
                                    :name="trim(auth()->user()->first_name . ' ' . auth()->user()->last_name)"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">
                                        {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                                    </flux:heading>

                                    <flux:text class="truncate">
                                        {{ auth()->user()->email }}
                                    </flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item
                        :href="route('profile.edit')"
                        icon="user"
                        wire:navigate
                    >
                        Mi perfil
                    </flux:menu.item>

                    <flux:menu.item
                        :href="route('appearance.edit')"
                        icon="adjustments-horizontal"
                        wire:navigate
                    >
                        Apariencia
                    </flux:menu.item>

                    <flux:menu.item
                        :href="route('security.edit')"
                        icon="shield-check"
                        wire:navigate
                    >
                        Seguridad
                    </flux:menu.item>

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
                            Cerrar sesiÃƒÂ³n
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Contenido de la pÃƒÂ¡gina --}}
        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>




