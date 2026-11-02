@props(['points', 'money' => true])

@php
    $max = max(1, ...array_column($points, 'value'));
    $format = fn ($value) => ($money ? '$ ' : '').number_format($value, 0, ',', '.');
@endphp

<div class="flex h-44 items-end gap-2" role="img" aria-label="Gráfico de barras">
    @foreach ($points as $point)
        <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1" title="{{ $point['label'] }}: {{ $format($point['value']) }}">
            <div class="flex w-full flex-1 items-end">
                <div class="w-full rounded-t-md bg-indigo-500" style="height: {{ max($point['value'] > 0 ? 3 : 1, round($point['value'] / $max * 100)) }}%"></div>
            </div>
            <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $point['label'] }}</span>
        </div>
    @endforeach
</div>
