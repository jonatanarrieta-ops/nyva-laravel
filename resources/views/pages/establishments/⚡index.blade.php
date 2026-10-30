<?php

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

        public function updatedSearch(): void
        {
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
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">
            Establecimientos
        </h1>

        <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
            Consulta y administra los establecimientos asociados a tu cuenta.
        </p>
    </div>

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
                    <flux:icon name="store" class="size-8 text-neutral-500" />
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
                                        {{ $establishment->status === 'active'
                                            ? 'Activo'
                                            : ucfirst($establishment->status) }}
                                    </span>
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
