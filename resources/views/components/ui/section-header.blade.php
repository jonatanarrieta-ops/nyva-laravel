@props(['title', 'subtitle' => null])

<div {{ $attributes->class('flex flex-wrap items-center justify-between gap-3') }}>
    <div class="min-w-0">
        <h2 class="font-semibold text-slate-800">{{ $title }}</h2>

        @if ($subtitle)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
