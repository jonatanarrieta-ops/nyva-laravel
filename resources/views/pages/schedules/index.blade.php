<?php

use App\Concerns\WithEstablishmentContext;
use App\Models\Schedule;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Horarios')] class extends Component {
    use WithEstablishmentContext;

    public array $days = [];

    protected function establishmentPermission(): string
    {
        return 'settings';
    }

    public function mount(): void
    {
        $this->loadDays();
    }

    public function updatedCurrentEstablishmentId(): void
    {
        session(['current_establishment_id' => $this->currentEstablishment()->id]);
        $this->resetValidation();
        $this->loadDays();
    }

    private function loadDays(): void
    {
        $saved = $this->currentEstablishment()->schedules->keyBy(fn (Schedule $schedule) => strtolower($schedule->day_of_week));

        $this->days = collect(Schedule::DAYS)
            ->map(fn ($label, $day) => [
                'enabled' => $saved->has($day) && $saved[$day]->status === 'active',
                'opens_at' => substr($saved[$day]->opens_at ?? '08:00', 0, 5),
                'closes_at' => substr($saved[$day]->closes_at ?? '20:00', 0, 5),
            ])
            ->all();
    }

    public function save(): void
    {
        $establishment = $this->currentEstablishment();

        Gate::authorize('update', $establishment);

        $this->validate([
            'days.*.enabled' => ['boolean'],
            'days.*.opens_at' => ['required', 'date_format:H:i'],
            'days.*.closes_at' => ['required', 'date_format:H:i', 'different:days.*.opens_at'],
        ], [
            'days.*.opens_at.date_format' => 'Ingresa una hora válida.',
            'days.*.closes_at.date_format' => 'Ingresa una hora válida.',
            'days.*.closes_at.different' => 'La apertura y el cierre deben ser distintos.',
        ]);

        foreach (array_keys(Schedule::DAYS) as $day) {
            $values = $this->days[$day] ?? null;

            abort_if($values === null, 422);

            $establishment->schedules()->whereRaw('LOWER(day_of_week) = ?', [$day])->delete();

            $establishment->schedules()->create([
                'day_of_week' => $day,
                'opens_at' => $values['opens_at'],
                'closes_at' => $values['closes_at'],
                'status' => $values['enabled'] ? 'active' : 'inactive',
            ]);
        }

        session()->flash('notice', 'Horarios actualizados.');
    }

    public function with(): array
    {
        return ['establishments' => $this->establishmentOptions()];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Horarios de atención" subtitle="Fuera de estos horarios no se reciben pedidos.">
        <x-establishment-picker :establishments="$establishments" />
    </x-page-header>

    <x-alerts />

    <form wire:submit="save" class="nyva-card flex flex-col gap-1 p-5">
        @foreach (Schedule::DAYS as $day => $label)
            <div wire:key="day-{{ $day }}" class="grid grid-cols-2 items-center gap-3 border-b border-zinc-100 py-3 last:border-0 sm:grid-cols-[1fr_10rem_10rem] dark:border-zinc-800">
                <label class="col-span-2 flex items-center gap-3 text-sm font-medium sm:col-span-1">
                    <input type="checkbox" wire:model.live="days.{{ $day }}.enabled">
                    {{ $label }}
                </label>

                @if ($days[$day]['enabled'])
                    <div>
                        <label for="opens-{{ $day }}" class="sr-only">Apertura</label>
                        <input id="opens-{{ $day }}" type="time" wire:model="days.{{ $day }}.opens_at" class="nyva-input">
                        @error("days.$day.opens_at") <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="closes-{{ $day }}" class="sr-only">Cierre</label>
                        <input id="closes-{{ $day }}" type="time" wire:model="days.{{ $day }}.closes_at" class="nyva-input">
                        @error("days.$day.closes_at") <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                @else
                    <span class="col-span-2 text-sm text-zinc-500">Cerrado</span>
                @endif
            </div>
        @endforeach

        <div class="flex justify-end pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar horarios</button>
        </div>
    </form>
</div>
