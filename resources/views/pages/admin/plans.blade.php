<?php

use App\Models\Plan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Planes')] class extends Component {
    public bool $showForm = false;
    public ?int $planId = null;

    public string $name = '';
    public string $description = '';
    public string $monthly_price = '';
    public string $annual_price = '';
    public string $duration_days = '30';
    public string $product_limit = '';
    public string $user_limit = '';
    public array $features = [];
    public bool $active = true;

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('planId', 'name', 'description', 'monthly_price', 'annual_price', 'duration_days', 'product_limit', 'user_limit', 'features', 'active');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $plan = Plan::findOrFail($id);

        $this->resetValidation();
        $this->planId = $plan->id;
        $this->name = $plan->name;
        $this->description = $plan->description ?? '';
        $this->monthly_price = (string) $plan->monthly_price;
        $this->annual_price = (string) $plan->annual_price;
        $this->duration_days = (string) $plan->duration_days;
        $this->product_limit = (string) $plan->product_limit;
        $this->user_limit = (string) $plan->user_limit;
        $this->features = $plan->features ?? [];
        $this->active = $plan->status === 'active';
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
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'annual_price' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3660'],
            'product_limit' => ['nullable', 'integer', 'min:1'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['array'],
            'features.*' => [Rule::in(array_keys(Plan::FEATURES))],
        ]);

        $values = [
            ...$data,
            'description' => $data['description'] ?: null,
            'annual_price' => $data['annual_price'] === '' ? null : $data['annual_price'],
            'product_limit' => $data['product_limit'] === '' ? null : $data['product_limit'],
            'user_limit' => $data['user_limit'] === '' ? null : $data['user_limit'],
            'features' => array_values($data['features']),
            'status' => $this->active ? 'active' : 'inactive',
        ];

        Plan::updateOrCreate(['id' => $this->planId], $values);

        session()->flash('notice', $this->planId ? 'Plan actualizado.' : 'Plan creado.');

        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $plan = Plan::findOrFail($id);

        if ($plan->subscriptions()->exists()) {
            session()->flash('failure', 'Este plan tiene suscripciones. Desactívalo en lugar de eliminarlo.');

            return;
        }

        $plan->delete();

        session()->flash('notice', 'Plan eliminado.');
    }

    public function with(): array
    {
        return ['plans' => Plan::query()->withCount('subscriptions')->orderBy('monthly_price')->get()];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Planes" subtitle="Define precios, límites y funcionalidades.">
        <button type="button" wire:click="create" class="nyva-btn">Nuevo plan</button>
    </x-page-header>

    <x-alerts />

    @if ($showForm)
        <section class="nyva-card p-5">
            <h2 class="mb-5 text-lg font-semibold">{{ $planId ? 'Editar plan' : 'Nuevo plan' }}</h2>

            <form wire:submit="save" class="flex flex-col gap-5">
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label for="name" class="nyva-label">Nombre</label>
                        <input id="name" type="text" wire:model="name" class="nyva-input">
                        @error('name') <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>

                    @foreach (['monthly_price' => 'Precio mensual', 'annual_price' => 'Precio anual (opcional)', 'duration_days' => 'Duración en días', 'product_limit' => 'Límite de productos (vacío: sin límite)', 'user_limit' => 'Límite de usuarios (vacío: sin límite)'] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
                            <input id="{{ $field }}" type="number" min="0" step="any" wire:model="{{ $field }}" class="nyva-input">
                            @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label for="description" class="nyva-label">Descripción</label>
                    <textarea id="description" wire:model="description" rows="2" class="nyva-input"></textarea>
                    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <fieldset>
                    <legend class="nyva-label">Funcionalidades</legend>
                    <div class="flex flex-wrap gap-4 text-sm">
                        @foreach (Plan::FEATURES as $value => $label)
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model="features" value="{{ $value }}"> {{ $label }}</label>
                        @endforeach
                    </div>
                    @error('features.*') <p class="nyva-error">{{ $message }}</p> @enderror
                </fieldset>

                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="active"> Plan activo</label>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancel" class="nyva-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
                </div>
            </form>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($plans as $plan)
            <article wire:key="plan-{{ $plan->id }}" class="nyva-card flex flex-col gap-3 p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold">{{ $plan->name }}</h2>
                    <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium dark:bg-zinc-800">{{ $plan->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                </div>

                <p class="text-2xl font-bold">$ {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}<span class="text-sm font-normal text-zinc-500"> / mes</span></p>

                @if ($plan->description)
                    <p class="text-sm text-zinc-500">{{ $plan->description }}</p>
                @endif

                <ul class="flex flex-col gap-1 text-sm">
                    <li>{{ $plan->duration_days }} días de duración</li>
                    <li>{{ $plan->product_limit ?? 'Sin límite de' }} productos</li>
                    <li>{{ $plan->user_limit ?? 'Sin límite de' }} usuarios</li>
                    @foreach ($plan->features ?? [] as $feature)
                        <li>{{ Plan::FEATURES[$feature] ?? $feature }}</li>
                    @endforeach
                </ul>

                <div class="mt-auto flex items-center justify-between border-t border-zinc-100 pt-3 text-sm dark:border-zinc-800">
                    <span class="text-zinc-500">{{ $plan->subscriptions_count }} suscripciones</span>
                    <span class="flex gap-3">
                        <button type="button" wire:click="edit({{ $plan->id }})" class="nyva-link">Editar</button>
                        <button type="button" wire:click="delete({{ $plan->id }})" wire:confirm="¿Eliminar este plan?" class="nyva-link-danger">Eliminar</button>
                    </span>
                </div>
            </article>
        @empty
            <p class="py-10 text-center text-sm text-zinc-500 md:col-span-2 xl:col-span-3">Todavía no hay planes.</p>
        @endforelse
    </div>
</div>
