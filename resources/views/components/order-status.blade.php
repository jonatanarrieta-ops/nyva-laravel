@props(['status', 'label'])

@php
    $colors = [
        'pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'confirmed' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'preparing' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'shipped' => 'bg-violet-50 text-violet-700 ring-violet-200',
        'delivered' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
    ];
@endphp

<span class="nyva-badge ring-1 ring-inset {{ $colors[$status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
    <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $label }}
</span>
