@props(['status', 'label'])

@php
    $colors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'preparing' => 'bg-indigo-100 text-indigo-800',
        'shipped' => 'bg-violet-100 text-violet-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $colors[$status] ?? 'bg-zinc-100 text-zinc-700' }}">
    {{ $label }}
</span>
