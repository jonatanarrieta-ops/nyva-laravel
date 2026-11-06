@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Horarios de atención" subtitle="Fuera de estos horarios no se reciben pedidos.">
            <x-establishment-switcher :establishments="$establishments" :current="$establishment" :action="route('schedules.index')" />
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        <form method="POST" action="{{ route('schedules.update') }}" class="nyva-card flex flex-col gap-1 p-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="establishment" value="{{ $establishment->id }}">

            <div class="hidden grid-cols-[1fr_10rem_10rem] gap-3 pb-2 text-xs font-semibold uppercase tracking-wide text-zinc-500 sm:grid">
                <span>Día</span>
                <span>Apertura</span>
                <span>Cierre</span>
            </div>

            @foreach ($days as $day => $values)
                <div class="grid grid-cols-2 items-center gap-3 border-b border-zinc-100 py-3 last:border-0 sm:grid-cols-[1fr_10rem_10rem] dark:border-zinc-800">
                    <label class="col-span-2 flex items-center gap-3 text-sm font-medium sm:col-span-1">
                        <input type="hidden" name="days[{{ $day }}][enabled]" value="0">
                        <input type="checkbox" name="days[{{ $day }}][enabled]" value="1" @checked(old("days.$day.enabled", $values['enabled']))>
                        {{ $values['label'] }}
                    </label>

                    <div>
                        <label for="opens-{{ $day }}" class="sr-only">Apertura</label>
                        <input id="opens-{{ $day }}" name="days[{{ $day }}][opens_at]" type="time" value="{{ old("days.$day.opens_at", $values['opens_at']) }}" class="nyva-input">
                        @error("days.$day.opens_at") <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="closes-{{ $day }}" class="sr-only">Cierre</label>
                        <input id="closes-{{ $day }}" name="days[{{ $day }}][closes_at]" type="time" value="{{ old("days.$day.closes_at", $values['closes_at']) }}" class="nyva-input">
                        @error("days.$day.closes_at") <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            <p class="pt-3 text-xs text-zinc-500">Los días sin marcar quedan cerrados.</p>

            <div class="flex justify-end pt-4">
                <button type="submit" class="nyva-btn">Guardar horarios</button>
            </div>
        </form>
    </div>
@endsection
