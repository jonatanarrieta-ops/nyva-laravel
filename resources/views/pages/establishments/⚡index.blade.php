<?php

use App\Actions\PlaceOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
    #[Title('Establecimientos')]
    class extends Component {
        use WithPagination;

        public string $search = '';

        public bool $showForm = false;
        public ?int $establishmentId = null;

        public string $name = '';
        public string $type = '';
        public string $description = '';
        public string $address = '';
        public string $phone = '';
        public string $email = '';
        public bool $status = true;

        public function updatedSearch(): void
        {
            $this->resetPage();
        }

        public function createEstablishment(): void
        {
            $this->resetValidation();
            $this->reset('establishmentId', 'name', 'type', 'description', 'address', 'phone', 'email', 'status');
            $this->showForm = true;
        }

        public function editEstablishment(int $id): void
        {
            $establishment = Auth::user()->establishments()->findOrFail($id);

            $this->resetValidation();
            $this->establishmentId = $establishment->id;
            $this->name = $establishment->name;
            $this->type = $establishment->type;
            $this->description = $establishment->description ?? '';
            $this->address = $establishment->address;
            $this->phone = $establishment->phone;
            $this->email = $establishment->email ?? '';
            $this->status = $establishment->status === 'active';
            $this->showForm = true;
        }

        public function cancelForm(): void
        {
            $this->showForm = false;
            $this->establishmentId = null;
            $this->resetValidation();
        }

        public function save(): void
        {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:150'],
                'type' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:5000'],
                'address' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'regex:'.PlaceOrder::PHONE_REGEX],
                'email' => ['nullable', 'email', 'max:255'],
                'status' => ['required', 'boolean'],
            ], [
                'name.required' => 'El nombre del establecimiento es obligatorio.',
                'type.required' => 'El tipo de establecimiento es obligatorio.',
                'address.required' => 'La dirección es obligatoria.',
                'phone.required' => 'El teléfono es obligatorio.',
                'phone.regex' => 'Ingresa un teléfono válido.',
                'email.email' => 'Ingresa un correo válido.',
            ]);

            $data = [
                'name' => trim($validated['name']),
                'type' => trim($validated['type']),
                'description' => trim($validated['description'] ?? '') ?: null,
                'address' => trim($validated['address']),
                'phone' => trim($validated['phone']),
                'email' => trim($validated['email'] ?? '') ?: null,
                'status' => $validated['status'] ? 'active' : 'inactive',
            ];

            if ($this->establishmentId !== null) {
                Auth::user()->establishments()->findOrFail($this->establishmentId)->update($data);
                $message = 'Establecimiento actualizado correctamente.';
            } else {
                Auth::user()->establishments()->create([...$data, 'registered_at' => now()]);
                $message = 'Establecimiento creado correctamente.';
            }

            $this->showForm = false;
            $this->establishmentId = null;
            $this->resetPage();

            session()->flash('establishment-message', $message);
        }

        public function deleteEstablishment(int $id): void
        {
            $establishment = Auth::user()->establishments()->findOrFail($id);

            if ($establishment->orders()->exists() || $establishment->categories()->exists()) {
                session()->flash(
                    'establishment-error',
                    'No puedes eliminar un establecimiento con categorías o pedidos. Puedes desactivarlo.'
                );

                return;
            }

            $establishment->delete();

            session()->flash('establishment-message', 'Establecimiento eliminado correctamente.');
            $this->resetPage();
        }

        public function with(): array
        {
            $search = trim($this->search);

            $establishments = Auth::user()
                ->establishments()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('registered_at')
                ->paginate(10);

            return [
                'establishments' => $establishments,
            ];
        }
    };
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Establecimientos
            </h1>

            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                Consulta y administra los establecimientos asociados a tu cuenta.
            </p>
        </div>

        <button
            type="button"
            wire:click="createEstablishment"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            <span>+</span> Nuevo establecimiento
        </button>
    </div>

    @if (session('establishment-message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('establishment-message') }}
        </div>
    @endif

    @if (session('establishment-error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            {{ session('establishment-error') }}
        </div>
    @endif

    @if ($showForm)
        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-5 text-lg font-semibold">
                {{ $establishmentId ? 'Editar establecimiento' : 'Nuevo establecimiento' }}
            </h2>

            <form wire:submit="save" class="flex flex-col gap-5">
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">Nombre</label>
                        <input id="name" type="text" wire:model="name" maxlength="150" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="type" class="mb-1.5 block text-sm font-medium">Tipo</label>
                        <input id="type" type="text" wire:model="type" maxlength="50" placeholder="Ej. Restaurante, Cafetería" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="mb-1.5 block text-sm font-medium">Dirección</label>
                        <input id="address" type="text" wire:model="address" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @error('address') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-sm font-medium">Teléfono</label>
                        <input id="phone" type="tel" wire:model="phone" maxlength="20" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium">
                            Correo <span class="font-normal text-zinc-400">(opcional)</span>
                        </label>
                        <input id="email" type="email" wire:model="email" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium">
                        Descripción <span class="font-normal text-zinc-400">(opcional)</span>
                    </label>
                    <textarea id="description" wire:model="description" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="status" class="rounded border-zinc-300">
                    Activo y visible en el catálogo público
                </label>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelForm" class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium dark:border-zinc-700">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                        Guardar establecimiento
                    </button>
                </div>
            </form>
        </section>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="w-full sm:max-w-md">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, tipo o dirección..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <div class="text-sm text-neutral-500 dark:text-neutral-400">
            {{ $establishments->total() }}
            {{ $establishments->total() === 1 ? 'establecimiento' : 'establecimientos' }}
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        @if ($establishments->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                <div class="mb-4 rounded-full bg-neutral-100 p-4 dark:bg-neutral-800">
                    <flux:icon name="building-storefront" class="size-8 text-neutral-500" />
                </div>

                <h2 class="text-lg font-medium">
                    {{ $search !== '' ? 'No se encontraron resultados' : 'Aún no tienes establecimientos' }}
                </h2>

                <p class="mt-2 max-w-md text-sm text-neutral-500 dark:text-neutral-400">
                    {{ $search !== ''
                        ? 'Prueba con otro nombre, tipo o dirección.'
                        : 'Aquí aparecerán los establecimientos asociados a tu cuenta.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nombre</th>
                            <th class="px-5 py-3 font-medium">Tipo</th>
                            <th class="px-5 py-3 font-medium">Dirección</th>
                            <th class="px-5 py-3 font-medium">Estado</th>
                            <th class="px-5 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($establishments as $establishment)
                            <tr
                                wire:key="establishment-{{ $establishment->id }}"
                                class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50"
                            >
                                <td class="px-5 py-4 font-medium">
                                    {{ $establishment->name }}
                                </td>

                                <td class="px-5 py-4">
                                    {{ $establishment->type }}
                                </td>

                                <td class="px-5 py-4">
                                    {{ $establishment->address }}
                                </td>

                                <td class="px-5 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                            => $establishment->status === 'active',
                                        'bg-neutral-100 text-neutral-700 dark:bg-neutral-700 dark:text-neutral-200'
                                            => $establishment->status !== 'active',
                                    ])>
                                        {{ $establishment->status === 'active' ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex justify-end gap-3 whitespace-nowrap">
                                        <button type="button" wire:click="editEstablishment({{ $establishment->id }})" class="font-medium text-blue-600 hover:underline">
                                            Editar
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="deleteEstablishment({{ $establishment->id }})"
                                            wire:confirm="¿Seguro que deseas eliminar este establecimiento?"
                                            class="font-medium text-red-600 hover:underline"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-neutral-200 px-5 py-4 dark:border-neutral-700">
                {{ $establishments->links() }}
            </div>
        @endif
    </div>
</div>
