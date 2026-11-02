@props(['items'])

@php
    $total = max(1, array_sum(array_column($items, 'value')));
@endphp

<div class="flex flex-col gap-3">
    @foreach ($items as $item)
        <div>
            <div class="mb-1 flex items-center justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-300">{{ $item['label'] }}</span>
                <span class="font-semibold">{{ $item['value'] }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div class="h-full rounded-full {{ $item['color'] }}" style="width: {{ round($item['value'] / $total * 100) }}%"></div>
            </div>
        </div>
    @endforeach
</div>
