@props(['label' => null, 'name', 'type' => 'text', 'hint' => null])

<div>
    @if ($label)
        <label for="{{ $name }}" class="nyva-label">{{ $label }}</label>
    @endif

    <input
        id="{{ $name }}"
        type="{{ $type }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class('nyva-input') }}
    >

    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="nyva-error">{{ $message }}</p>
    @enderror
</div>
