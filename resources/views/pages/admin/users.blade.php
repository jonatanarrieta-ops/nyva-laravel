<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Usuarios')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public bool $showForm = false;
    public ?int $userId = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'establishment_admin';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('userId', 'first_name', 'last_name', 'email', 'password', 'role');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);

        $this->resetValidation();
        $this->userId = $user->id;
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role === 'admin' ? 'platform_admin' : $user->role;
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($this->userId)],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        if ($this->userId === Auth::id() && $data['role'] !== 'platform_admin') {
            $this->addError('role', 'No puedes quitarte tu propio acceso de administrador.');

            return;
        }

        if (! $data['password']) {
            unset($data['password']);
        }

        $user = $this->userId ? User::findOrFail($this->userId) : new User(['registered_at' => now()]);

        if (! $this->userId) {
            $user->status = 'active';
        }

        $user->forceFill($data)->save();

        session()->flash('notice', $this->userId ? 'Usuario actualizado.' : 'Usuario creado.');

        $this->showForm = false;
    }

    public function toggleStatus(int $id): void
    {
        if ($id === Auth::id()) {
            session()->flash('failure', 'No puedes desactivar tu propia cuenta.');

            return;
        }

        $user = User::findOrFail($id);
        $user->forceFill(['status' => $user->isActive() ? 'inactive' : 'active'])->save();

        session()->flash('notice', 'Estado del usuario actualizado.');
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'users' => User::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                })
                ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
                ->orderBy('first_name')
                ->paginate(15),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Usuarios" subtitle="Administra las cuentas y sus permisos.">
        <button type="button" wire:click="create" class="nyva-btn">Nuevo usuario</button>
    </x-page-header>

    <x-alerts />

    @if ($showForm)
        <section class="nyva-card p-5">
            <h2 class="mb-5 text-lg font-semibold">{{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>

            <form wire:submit="save" class="grid gap-5 md:grid-cols-2">
                @foreach (['first_name' => 'Nombres', 'last_name' => 'Apellidos', 'email' => 'Correo'] as $field => $label)
                    <div>
                        <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
                        <input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="nyva-input">
                        @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div>
                    <label for="password" class="nyva-label">Contraseña {{ $userId ? '(déjala vacía para conservarla)' : '' }}</label>
                    <input id="password" type="password" wire:model="password" autocomplete="new-password" class="nyva-input">
                    @error('password') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="role" class="nyva-label">Rol</label>
                    <select id="role" wire:model="role" class="nyva-input">
                        @foreach (User::ROLES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
                    <button type="button" wire:click="cancel" class="nyva-btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
                </div>
            </form>
        </section>
    @endif

    <section class="nyva-card overflow-hidden">
        <div class="flex flex-col gap-3 p-4 sm:flex-row">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar usuario..." aria-label="Buscar" class="nyva-input sm:max-w-xs">
            <select wire:model.live="roleFilter" aria-label="Filtrar por rol" class="nyva-input sm:w-60">
                <option value="">Todos los roles</option>
                @foreach (User::ROLES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="nyva-table">
                <thead>
                    <tr><th>Usuario</th><th>Rol</th><th>Estado</th><th class="text-right">Acciones</th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="font-medium">{{ $user->first_name }} {{ $user->last_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                            </td>
                            <td>{{ User::ROLES[$user->role] ?? $user->role }}</td>
                            <td>{{ $user->isActive() ? 'Activo' : 'Inactivo' }}</td>
                            <td>
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <button type="button" wire:click="edit({{ $user->id }})" class="nyva-link">Editar</button>
                                    <button type="button" wire:click="toggleStatus({{ $user->id }})" wire:confirm="¿Cambiar el estado de esta cuenta?" class="nyva-link-danger">
                                        {{ $user->isActive() ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $users->links() }}</div>
    </section>
</div>
