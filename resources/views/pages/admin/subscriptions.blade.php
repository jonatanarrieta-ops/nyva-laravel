<?php

use App\Models\Establishment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Suscripciones')] class extends Component {
    use WithPagination;

    public bool $showForm = false;
    public ?int $subscriptionId = null;

    public string $establishment_id = '';
    public string $plan_id = '';
    public string $billing_period = 'monthly';
    public string $start_date = '';
    public string $end_date = '';
    public string $status = 'active';

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('subscriptionId', 'establishment_id', 'plan_id', 'billing_period', 'end_date', 'status');
        $this->start_date = now()->toDateString();
        $this->showForm = true;
    }

    public function updatedPlanId(): void
    {
        $this->suggestEndDate();
    }

    public function updatedStartDate(): void
    {
        $this->suggestEndDate();
    }

    private function suggestEndDate(): void
    {
        $plan = Plan::find($this->plan_id);

        if ($plan && $this->start_date) {
            $this->end_date = now()->parse($this->start_date)->addDays($plan->duration_days)->toDateString();
        }
    }

    public function edit(int $id): void
    {
        $subscription = Subscription::findOrFail($id);

        $this->resetValidation();
        $this->subscriptionId = $subscription->id;
        $this->establishment_id = (string) $subscription->establishment_id;
        $this->plan_id = (string) $subscription->plan_id;
        $this->billing_period = $subscription->billing_period;
        $this->start_date = $subscription->start_date->toDateString();
        $this->end_date = $subscription->end_date?->toDateString() ?? '';
        $this->status = $subscription->status;
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'establishment_id' => ['required', Rule::exists(Establishment::class, 'id')],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'billing_period' => ['required', Rule::in(['monthly', 'annual'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'cancelled'])],
        ], [
            'establishment_id.required' => 'Selecciona un establecimiento.',
            'plan_id.required' => 'Selecciona un plan.',
            'end_date.after_or_equal' => 'El vencimiento no puede ser anterior al inicio.',
        ]);

        $data['end_date'] = $data['end_date'] ?: null;

        Subscription::updateOrCreate(['id' => $this->subscriptionId], $data);

        session()->flash('notice', $this->subscriptionId ? 'Suscripción actualizada.' : 'Suscripción creada.');

        $this->showForm = false;
    }

    public function with(): array
    {
        return [
            'subscriptions' => Subscription::query()
                ->with(['establishment', 'plan'])
                ->orderByDesc('start_date')
                ->paginate(15),
            'establishments' => Establishment::orderBy('name')->get(['id', 'name']),
            'plans' => Plan::where('status', 'active')->orderBy('name')->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Suscripciones" subtitle="Asigna planes y controla sus vencimientos.">
        <button type="button" wire:click="create" class="nyva-btn">Nueva suscripción</button>
    </x-page-header>

    <x-alerts />

    @if ($showForm)
        <section class="nyva-card p-5">
            <h2 class="mb-5 text-lg font-semibold">{{ $subscriptionId ? 'Editar suscripción' : 'Nueva suscripción' }}</h2>

            <form wire:submit="save" class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="establishment_id" class="nyva-label">Establecimiento</label>
                    <select id="establishment_id" wire:model="establishment_id" class="nyva-input">
                        <option value="">Selecciona</option>
                        @foreach ($establishments as $establishment)
                            <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                        @endforeach
                    </select>
                    @error('establishment_id') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="plan_id" class="nyva-label">Plan</label>
                    <select id="plan_id" wire:model.live="plan_id" class="nyva-input">
                        <option value="">Selecciona</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                    @error('plan_id') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="billing_period" class="nyva-label">Periodicidad</label>
                    <select id="billing_period" wire:model="billing_period" class="nyva-input">
                        <option value="monthly">Mensual</option>
                        <option value="annual">Anual</option>
                    </select>
                </div>

                <div>
                    <label for="status" class="nyva-label">Estado</label>
                    <select id="status" wire:model="status" class="nyva-input">
                        <option value="active">Activa</option>
                        <option value="inactive">Inactiva</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                    @error('status') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="start_date" class="nyva-label">Inicio</label>
                    <input id="start_date" type="date" wire:model.live="start_date" class="nyva-input">
                    @error('start_date') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="end_date" class="nyva-label">Vencimiento</label>
                    <input id="end_date" type="date" wire:model="end_date" class="nyva-input">
                    @error('end_date') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
                    <button type="button" wire:click="cancel" class="nyva-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
                </div>
            </form>
        </section>
    @endif

    <section class="nyva-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="nyva-table">
                <thead>
                    <tr><th>Establecimiento</th><th>Plan</th><th>Periodo</th><th>Vencimiento</th><th>Estado</th><th>Uso</th><th class="text-right">Acciones</th></tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $subscription)
                        @php
                            $expired = $subscription->end_date?->endOfDay()->isPast();
                            $plan = $subscription->plan;
                        @endphp
                        <tr wire:key="subscription-{{ $subscription->id }}">
                            <td class="font-medium">{{ $subscription->establishment?->name }}</td>
                            <td>{{ $plan?->name }}</td>
                            <td>{{ $subscription->billing_period === 'annual' ? 'Anual' : 'Mensual' }}</td>
                            <td>{{ $subscription->end_date?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                            <td>
                                <span @class([
                                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-red-100 text-red-800' => $expired,
                                    'bg-green-100 text-green-800' => ! $expired && $subscription->status === 'active',
                                    'bg-zinc-100 text-zinc-700' => ! $expired && $subscription->status !== 'active',
                                ])>
                                    {{ $expired ? 'Vencida' : ($subscription->status === 'active' ? 'Activa' : 'Inactiva') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $subscription->establishment?->productCount() }} / {{ $plan?->product_limit ?? '∞' }} productos
                            </td>
                            <td class="text-right"><button type="button" wire:click="edit({{ $subscription->id }})" class="nyva-link">Editar</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-zinc-500">Todavía no hay suscripciones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $subscriptions->links() }}</div>
    </section>
</div>
