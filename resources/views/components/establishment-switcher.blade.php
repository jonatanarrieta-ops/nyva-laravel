@props(['establishments', 'current', 'action'])

@if ($establishments->count() > 1)
    <form method="GET" action="{{ $action }}" class="w-full sm:w-64">
        <label for="establishment" class="sr-only">Establecimiento</label>
        <select id="establishment" name="establishment" onchange="this.form.submit()" class="nyva-input">
            @foreach ($establishments as $establishment)
                <option value="{{ $establishment->id }}" @selected($establishment->id === $current->id)>{{ $establishment->name }}</option>
            @endforeach
        </select>
    </form>
@endif
