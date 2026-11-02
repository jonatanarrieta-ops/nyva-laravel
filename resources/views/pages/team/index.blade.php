<?php

use App\Concerns\WithEstablishmentContext;
use App\Models\User;
use App\Services\PlanLimits;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Equipo')] class extends Component {
    use WithEstablishmentContext;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $password = '';
    public array $permissions = ['orders'];

    protected function establishmentPermission(): string
    {
        return 'settings';
    }

    public function add(PlanLimits $limits): void
    {
        $establishment = $this->currentEstablishment();

        Gate::authorize('manageTeam', $establishment);

        $limits->ensureCanAddUser($establishment);

        $data = $this->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in(array_keys(User::PERMISSIONS))],
        ], [
            'email.unique' => 'Este correo ya tiene una cuenta.',
            'permissions.required' => 'Selecciona al menos un permiso.',
            'permissions.min' => 'Selecciona al menos un permiso.',
        ]);

        $user = User::forceCreate([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'establishment_staff',
            'status' => 'active',
            'registered_at' => now(),
        ]);

        $establishment->members()->attach($user->id, ['permissions' => json_encode(array_values($data['permissions']))]);

        $this->reset('first_name', 'last_name', 'email', 'password', 'permissions');

        session()->flash('notice', 'Usuario agregado.');
    }

    public function remove(int $userId): void
    {
        $establishment = $this->currentEstablishment();

        Gate::authorize('manageTeam', $establishment);

        $user = $establishment->members()->findOrFail($userId);

        $establishment->members()->detach($user->id);
        $user->forceFill(['status' => 'inactive'])->save();

        session()->flash('notice', 'Usuario retirado.');
    }

    public function with(PlanLimits $limits): array
    {
        $establishment = $this->currentEstablishment();

        return [
            'establishments' => $this->establishmentOptions(),
            'owner' => $establishment->user,
            'members' => $establishment->members()->orderBy('first_name')->get(),
            'canManage' => Gate::allows('manageTeam', $establishment),
            'canAdd' => $limits->canAddUser($establishment),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Equipo" subtitle="Usuarios autorizados para gestionar tu establecimiento.">
        <x-establishment-picker :establishments="$establishments" />
    </x-page-header>

    <x-alerts />

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <section class="nyva-card overflow-hidden xl:col-span-2">
            <div class="overflow-x-auto">
                <table class="nyva-table">
                    <thead><tr><th>Usuario</th><th>Permisos</th><th class="text-right">Acciones</th></tr></thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="font-medium">{{ $owner?->first_name }} {{ $owner?->last_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $owner?->email }}</div>
                            </td>
                            <td>Propietario</td>
                            <td></td>
                        </tr>

                        @foreach ($members as $member)
                            <tr wire:key="member-{{ $member->id }}">
                                <td>
                                    <div class="font-medium">{{ $member->first_name }} {{ $member->last_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $member->email }}</div>
                                </td>
                                <td>
                                    {{ collect(json_decode($member->pivot->permissions, true))->map(fn ($permission) => User::PERMISSIONS[$permission] ?? $permission)->implode(', ') }}
                                </td>
                                <td class="text-right">
                                    @if ($canManage)
                                        <button type="button" wire:click="remove({{ $member->id }})" wire:confirm="¿Retirar este usuario? Su cuenta quedará inactiva." class="nyva-link-danger">Retirar</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($canManage)
            <section class="nyva-card p-5">
                <h2 class="mb-4 font-semibold">Agregar usuario</h2>

                @if ($canAdd)
                    <form wire:submit="add" class="flex flex-col gap-4">
                        @foreach (['first_name' => 'Nombres', 'last_name' => 'Apellidos', 'email' => 'Correo'] as $field => $label)
                            <div>
                                <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
                                <input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="nyva-input">
                                @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach

                        <div>
                            <label for="password" class="nyva-label">Contraseña inicial</label>
                            <input id="password" type="password" wire:model="password" autocomplete="new-password" class="nyva-input">
                            @error('password') <p class="nyva-error">{{ $message }}</p> @enderror
                        </div>

                        <fieldset>
                            <legend class="nyva-label">Permisos</legend>
                            <div class="flex flex-col gap-2 text-sm">
                                @foreach (User::PERMISSIONS as $value => $label)
                                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="permissions" value="{{ $value }}"> {{ $label }}</label>
                                @endforeach
                            </div>
                            @error('permissions') <p class="nyva-error">{{ $message }}</p> @enderror
                        </fieldset>

                        <button type="submit" wire:loading.attr="disabled" wire:target="add" class="nyva-btn">Agregar</button>
                    </form>
                @else
                    <p class="text-sm text-zinc-500">Tu plan no permite agregar más usuarios.</p>
                @endif
            </section>
        @endif
    </div>
</div>
