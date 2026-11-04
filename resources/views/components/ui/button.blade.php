@props(['variant' => 'primary', 'type' => 'button', 'href' => null, 'target' => null, 'icon' => null])

@php
    $classes = match ($variant) {
        'secondary' => 'nyva-btn-secondary',
        'danger' => 'nyva-btn-danger',
        default => 'nyva-btn',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <flux:icon :name="$icon" class="size-4" />
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @if ($target) wire:loading.attr="disabled" wire:target="{{ $target }}" @endif
        {{ $attributes->class($classes) }}
    >
        @if ($target)
            <svg wire:loading wire:target="{{ $target }}" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3" />
                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
            </svg>
        @endif

        @if ($icon)
            <span @if ($target) wire:loading.remove wire:target="{{ $target }}" @endif class="contents">
                <flux:icon :name="$icon" class="size-4" />
            </span>
        @endif

        {{ $slot }}
    </button>
@endif
