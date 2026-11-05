<?php

use App\Actions\PlaceOrder;
use App\Models\Establishment;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Establecimientos')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public bool $showForm = false;
    public ?int $establishmentId = null;

    public string $name = '';
    public string $type = '';
    public string $description = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';

    public string $ownerMode = 'new';
    public string $ownerId = '';
    public string $ownerFirstName = '';
    public string $ownerLastName = '';
    public string $ownerEmail = '';
    public string $ownerPassword = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('establishmentId', 'name', 'type', 'description', 'address', 'phone', 'email', 'ownerMode', 'ownerId', 'ownerFirstName', 'ownerLastName', 'ownerEmail', 'ownerPassword');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $establishment = Establishment::findOrFail($id);

        $this->resetValidation();
        $this->establishmentId = $establishment->id;
        $this->name = $establishment->name;
        $this->type = $establishment->type;
        $this->description = $establishment->description ?? '';
        $this->address = $establishment->address;
        $this->phone = $establishment->phone;
        $this->email = $establishment->email ?? '';
        $this->ownerId = (string) $establishment->user_id;
        $this->ownerMode = 'existing';
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
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PlaceOrder::PHONE_REGEX],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'phone.regex' => 'Ingresa un teléfono válido.',
        ]);

        $owner = $this->validate($this->ownerMode === 'new'
            ? [
                'ownerFirstName' => ['required', 'string', 'max:100'],
                'ownerLastName' => ['required', 'string', 'max:100'],
                'ownerEmail' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'ownerPassword' => ['required', 'string', 'min:8'],
            ]
            : [
                'ownerId' => ['required', Rule::exists(User::class, 'id')->where('role', 'establishment_admin')],
            ]);

        $data['email'] = $data['email'] ?: null;
        $data['description'] = $data['description'] ?: null;

        DB::transaction(function () use ($data, $owner) {
            $ownerId = $this->ownerMode === 'new'
                ? User::forceCreate([
                    'first_name' => $owner['ownerFirstName'],
                    'last_name' => $owner['ownerLastName'],
                    'email' => $owner['ownerEmail'],
                    'password' => $owner['ownerPassword'],
                    'role' => 'establishment_admin',
                    'status' => 'active',
                    'registered_at' => now(),
                ])->id
                : (int) $owner['ownerId'];

            if ($this->establishmentId !== null) {
                $establishment = Establishment::findOrFail($this->establishmentId);
                $establishment->fill($data);
                $establishment->user_id = $ownerId;
                $establishment->save();

                return;
            }

            $establishment = new Establishment([...$data, 'registered_at' => now()]);
            $establishment->user_id = $ownerId;
            $establishment->status = 'active';
            $establishment->save();
        });

        session()->flash('notice', $this->establishmentId ? 'Establecimiento actualizado.' : 'Establecimiento creado.');

        $this->showForm = false;
        $this->resetPage();
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(array_key_exists($status, Establishment::STATUSES), 422);

        $establishment = Establishment::findOrFail($id);
        $establishment->forceFill(['status' => $status])->save();

        if ($status === 'suspended') {
            $establishment->user?->notify(new AdminAlert(
                'Establecimiento suspendido',
                "{$establishment->name} fue suspendido y no recibirá pedidos.",
            ));
        }

        session()->flash('notice', 'Estado actualizado.');
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'establishments' => Establishment::query()
                ->with('user')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderBy('name')
                ->paginate(10),
            'owners' => User::where('role', 'establishment_admin')->orderBy('first_name')->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Establecimientos" subtitle="Registra, edita y controla los negocios de la plataforma.">
        <button type="button" wire:click="create" class="nyva-btn">Nuevo establecimiento</button>
    </x-page-header>

    <x-alerts />

    @if ($showForm)
        <section class="nyva-card p-5">
            <h2 class="mb-5 text-lg font-semibold">{{ $establishmentId ? 'Editar establecimiento' : 'Nuevo establecimiento' }}</h2>

            <form wire:submit="save" class="flex flex-col gap-5">
                <div class="grid gap-5 md:grid-cols-2">
                    @foreach (['name' => 'Nombre', 'type' => 'Tipo (restaurante, hotel, cocina oculta)', 'address' => 'Dirección', 'phone' => 'Teléfono', 'email' => 'Correo (opcional)'] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
                            <input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="nyva-input">
                            @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div>
                    <label for="description" class="nyva-label">Descripción (opcional)</label>
                    <textarea id="description" wire:model="description" rows="3" class="nyva-input"></textarea>
                    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <fieldset class="flex flex-col gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <legend class="font-semibold">Propietario</legend>

                    <div class="flex flex-wrap gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="ownerMode" value="new"> Crear cuenta nueva</label>
                        <label class="flex items-center gap-2"><input type="radio" wire:model.live="ownerMode" value="existing"> Usar cuenta existente</label>
                    </div>

                    @if ($ownerMode === 'new')
                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="ownerFirstName" class="nyva-label">Nombres</label>
                                <input id="ownerFirstName" type="text" wire:model="ownerFirstName" class="nyva-input">
                                @error('ownerFirstName') <p class="nyva-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ownerLastName" class="nyva-label">Apellidos</label>
                                <input id="ownerLastName" type="text" wire:model="ownerLastName" class="nyva-input">
                                @error('ownerLastName') <p class="nyva-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ownerEmail" class="nyva-label">Correo de acceso</label>
                                <input id="ownerEmail" type="email" wire:model="ownerEmail" class="nyva-input">
                                @error('ownerEmail') <p class="nyva-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="ownerPassword" class="nyva-label">Contraseña inicial</label>
                                <input id="ownerPassword" type="password" wire:model="ownerPassword" autocomplete="new-password" class="nyva-input">
                                @error('ownerPassword') <p class="nyva-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @else
                        <div>
                            <label for="ownerId" class="nyva-label">Propietario</label>
                            <select id="ownerId" wire:model="ownerId" class="nyva-input">
                                <option value="">Selecciona un propietario</option>
                                @foreach ($owners as $owner)
                                    <option value="{{ $owner->id }}">{{ $owner->first_name }} {{ $owner->last_name }} ({{ $owner->email }})</option>
                                @endforeach
                            </select>
                            @error('ownerId') <p class="nyva-error">{{ $message }}</p> @enderror
                        </div>
                    @endif
                </fieldset>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancel" class="nyva-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
                </div>
            </form>
        </section>
    @endif

    <section class="nyva-card overflow-hidden">
        <div class="flex flex-col gap-3 p-4 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre..." aria-label="Buscar" class="nyva-input sm:max-w-xs">
            <select wire:model.live="statusFilter" aria-label="Filtrar por estado" class="nyva-input sm:w-52">
                <option value="">Todos los estados</option>
                @foreach (Establishment::STATUSES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="nyva-table">
                <thead>
                    <tr>
                        <th>Establecimiento</th>
                        <th>Propietario</th>
                        <th>Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($establishments as $establishment)
                        <tr wire:key="establishment-{{ $establishment->id }}">
                            <td>
                                <div class="font-medium">{{ $establishment->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $establishment->type }}</div>
                            </td>
                            <td>{{ $establishment->user?->email }}</td>
                            <td>{{ Establishment::STATUSES[$establishment->status] ?? $establishment->status }}</td>
                            <td>
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <button type="button" wire:click="edit({{ $establishment->id }})" class="nyva-link">Editar</button>

                                    @if ($establishment->status === 'suspended' || $establishment->status === 'inactive')
                                        <button type="button" wire:click="setStatus({{ $establishment->id }}, 'active')" class="nyva-link">Activar</button>
                                    @else
                                        <button type="button" wire:click="setStatus({{ $establishment->id }}, 'suspended')" wire:confirm="¿Suspender este establecimiento? Dejará de recibir pedidos." class="nyva-link-danger">Suspender</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-10 text-center text-zinc-500">No se encontraron establecimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $establishments->links() }}</div>
    </section>
</div>
