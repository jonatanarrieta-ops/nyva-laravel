<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        return view('admin.subscriptions.index', [
            'title' => 'Suscripciones',
            'subscriptions' => Subscription::query()
                ->with(['establishment', 'plan'])
                ->orderByDesc('start_date')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.subscriptions.create', [
            'title' => 'Nueva suscripción',
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Subscription::create($this->validated($request));

        return redirect()->route('admin.subscriptions.index')->with('success', 'Suscripción creada.');
    }

    public function edit(Subscription $subscription): View
    {
        return view('admin.subscriptions.edit', [
            'title' => 'Editar suscripción',
            'subscription' => $subscription,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $subscription->update($this->validated($request));

        return redirect()->route('admin.subscriptions.index')->with('success', 'Suscripción actualizada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'establishment_id' => ['required', Rule::exists(Establishment::class, 'id')],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'billing_period' => ['required', Rule::in(['monthly', 'annual'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'cancelled'])],
        ], [
            'establishment_id.required' => 'Selecciona un establecimiento.',
            'plan_id.required' => 'Selecciona un plan.',
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'end_date.after_or_equal' => 'El vencimiento no puede ser anterior al inicio.',
        ]);

        $data['end_date'] ??= Carbon::parse($data['start_date'])
            ->addDays(Plan::query()->whereKey((int) $data['plan_id'])->firstOrFail()->duration_days)
            ->toDateString();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'establishments' => Establishment::orderBy('name')->get(['id', 'name']),
            'plans' => Plan::where('status', 'active')->orderBy('name')->get(),
        ];
    }
}
