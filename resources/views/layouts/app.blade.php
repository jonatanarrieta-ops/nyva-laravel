<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        @hasSection('content')
            @yield('content')
        @else
            {{ $slot ?? '' }}
        @endif
    </flux:main>
</x-layouts::app.sidebar>
