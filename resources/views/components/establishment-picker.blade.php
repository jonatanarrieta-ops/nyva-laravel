@props(['establishments'])

@if ($establishments->count() > 1)
    <div class="w-full sm:w-64">
        <label for="currentEstablishmentId" class="sr-only">Establecimiento</label>
        <select id="currentEstablishmentId" wire:model.live="currentEstablishmentId" class="nyva-input">
            @foreach ($establishments as $establishment)
                <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
            @endforeach
        </select>
    </div>
@endif
