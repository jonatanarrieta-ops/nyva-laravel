@props(['label', 'value', 'hint' => null, 'tone' => 'indigo'])

@php
    $tones = [
        'indigo' => 'bg-indigo-500',
        'green' => 'bg-emerald-500',
        'amber' => 'bg-amber-500',
        'red' => 'bg-red-500',
        'violet' => 'bg-violet-500',
    ];
@endphp

<div class="nyva-card relative overflow-hidden p-5">
    <span class="absolute inset-y-0 start-0 w-1 {{ $tones[$tone] ?? $tones['indigo'] }}"></span>
    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
    <p class="mt-2 text-3xl font-bold tracking-tight">{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
    @endif
</div>
