@props(['label', 'value', 'hint' => null, 'tone' => 'indigo', 'icon' => null])

@php
    $tones = [
        'indigo' => 'bg-blue-50 text-blue-700',
        'green' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-red-50 text-red-700',
        'violet' => 'bg-sky-50 text-sky-700',
    ];
@endphp

<div class="nyva-card flex items-start justify-between gap-4 p-5">
    <div class="min-w-0">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <p class="mt-2 truncate text-2xl font-bold tracking-tight text-slate-800 sm:text-3xl">{{ $value }}</p>

        @if ($hint)
            <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>

    @if ($icon)
        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['indigo'] }}">
            <flux:icon :name="$icon" class="size-5" />
        </span>
    @endif
</div>
