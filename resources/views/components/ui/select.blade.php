@props(['label' => null, 'name'])

<div>
    @if ($label)
        <label for="{{ $name }}" class="nyva-label">{{ $label }}</label>
    @endif

    <select
        id="{{ $name }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class('nyva-input') }}
    >
        {{ $slot }}
    </select>

    @error($name)
        <p id="{{ $name }}-error" class="nyva-error">{{ $message }}</p>
    @enderror
</div>
