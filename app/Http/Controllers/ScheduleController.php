<?php

namespace App\Http\Controllers;

use App\Concerns\ResolvesCurrentEstablishment;
use App\Models\Schedule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScheduleController extends Controller
{
    use ResolvesCurrentEstablishment;

    public function index(Request $request): View
    {
        $establishment = $this->currentEstablishment($request, 'settings');
        $saved = $establishment->schedules->keyBy(fn (Schedule $schedule) => strtolower($schedule->day_of_week));

        $days = collect(Schedule::DAYS)->map(fn (string $label, string $day) => [
            'label' => $label,
            'enabled' => $saved->has($day) && $saved[$day]->status === 'active',
            'opens_at' => substr($saved[$day]->opens_at ?? '08:00', 0, 5),
            'closes_at' => substr($saved[$day]->closes_at ?? '20:00', 0, 5),
        ]);

        return view('schedules.index', [
            'title' => 'Horarios',
            'establishment' => $establishment,
            'establishments' => $this->establishmentOptions($request, 'settings'),
            'days' => $days,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $establishment = $this->currentEstablishment($request, 'settings');

        Gate::authorize('update', $establishment);

        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.enabled' => ['nullable', 'boolean'],
            'days.*.opens_at' => ['required', 'date_format:H:i'],
            'days.*.closes_at' => ['required', 'date_format:H:i', 'different:days.*.opens_at'],
        ], [
            'days.*.opens_at.required' => 'Ingresa la hora de apertura.',
            'days.*.closes_at.required' => 'Ingresa la hora de cierre.',
            'days.*.opens_at.date_format' => 'Ingresa una hora válida.',
            'days.*.closes_at.date_format' => 'Ingresa una hora válida.',
            'days.*.closes_at.different' => 'La apertura y el cierre deben ser distintos.',
        ]);

        foreach (array_keys(Schedule::DAYS) as $day) {
            $values = $data['days'][$day] ?? null;

            abort_if($values === null, 422);

            $establishment->schedules()->whereRaw('LOWER(day_of_week) = ?', [$day])->delete();

            $establishment->schedules()->create([
                'day_of_week' => $day,
                'opens_at' => $values['opens_at'],
                'closes_at' => $values['closes_at'],
                'status' => ! empty($values['enabled']) ? 'active' : 'inactive',
            ]);
        }

        return redirect()
            ->route('schedules.index', ['establishment' => $establishment->id])
            ->with('success', 'Horarios actualizados.');
    }
}
