@props(['tone' => 'success'])

@php
    [$class, $icon] = match ($tone) {
        'error' => ['nyva-alert-error', 'x-circle'],
        'warning' => ['nyva-alert-warning', 'exclamation-triangle'],
        'info' => ['nyva-alert-info', 'information-circle'],
        default => ['nyva-alert-success', 'check-circle'],
    };
@endphp

<div role="{{ $tone === 'error' ? 'alert' : 'status' }}" {{ $attributes->class($class) }}>
    <flux:icon :name="$icon" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
