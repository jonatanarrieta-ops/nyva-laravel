@props(['tone' => 'slate'])

@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700',
        'blue' => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200',
        'red' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-200',
    ];
@endphp

<span {{ $attributes->class(['nyva-badge', $tones[$tone] ?? $tones['slate']]) }}>{{ $slot }}</span>
