<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'title' => 'Planes',
            'plans' => Plan::query()->withCount('subscriptions')->orderBy('monthly_price')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', ['title' => 'Nuevo plan']);
    }

    public function store(Request $request): RedirectResponse
    {
        Plan::create($this->validated($request));

        return redirect()->route('admin.plans.index')->with('success', 'Plan creado.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', [
            'title' => 'Editar plan',
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $plan->update($this->validated($request));

        return redirect()->route('admin.plans.index')->with('success', 'Plan actualizado.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            return redirect()->route('admin.plans.index')->with('error', 'Este plan tiene suscripciones. Desactívalo en lugar de eliminarlo.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('success', 'Plan eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'annual_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'product_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::in(array_keys(Plan::FEATURES))],
            'status' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'El nombre del plan es obligatorio.',
            'monthly_price.required' => 'El precio mensual es obligatorio.',
            'duration_days.required' => 'La duración es obligatoria.',
            'features.*.in' => 'La funcionalidad seleccionada no es válida.',
        ]);

        return [
            ...$data,
            'features' => array_values($data['features'] ?? []),
            'status' => $request->boolean('status') ? 'active' : 'inactive',
        ];
    }
}
