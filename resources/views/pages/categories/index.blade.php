<?php

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
    #[Title('Categorías')]
    class extends Component {
        use WithPagination;

        public string $search = '';
        public string $filterEstablishment = '';

        public bool $showForm = false;
        public ?int $categoryId = null;

        public string $establishmentId = '';
        public string $name = '';
        public string $description = '';
        public bool $status = true;

        public function updatedSearch(): void
        {
            $this->resetPage();
        }

        public function updatedFilterEstablishment(): void
        {
            $this->resetPage();
        }

        private function ownedCategories()
        {
            return Category::query()
                ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('catalog')->select('id'));
        }

        public function createCategory(): void
        {
            $this->resetValidation();

            $this->categoryId = null;
            $this->establishmentId = '';
            $this->name = '';
            $this->description = '';
            $this->status = true;
            $this->showForm = true;
        }

        public function editCategory(int $id): void
        {
            $category = $this->ownedCategories()->findOrFail($id);

            $this->resetValidation();

            $this->categoryId = $category->id;
            $this->establishmentId = (string) $category->establishment_id;
            $this->name = $category->name;
            $this->description = $category->description ?? '';
            $this->status = $category->status === 'active';
            $this->showForm = true;
        }

        public function cancelForm(): void
        {
            $this->showForm = false;
            $this->categoryId = null;
            $this->resetValidation();
        }

        public function save(): void
        {
            $validated = $this->validate([
                'establishmentId' => ['required', 'integer'],
                'name' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:5000'],
                'status' => ['required', 'boolean'],
            ], [
                'establishmentId.required' => 'Selecciona un establecimiento.',
                'name.required' => 'El nombre de la categoría es obligatorio.',
                'name.max' => 'El nombre no puede superar los 100 caracteres.',
            ]);

            $establishment = Auth::user()
                ->accessibleEstablishments('catalog')
                ->whereKey($validated['establishmentId'])
                ->first();

            if (! $establishment) {
                $this->addError(
                    'establishmentId',
                    'El establecimiento seleccionado no pertenece a tu cuenta.'
                );

                return;
            }

            $data = [
                'establishment_id' => $establishment->id,
                'name' => trim($validated['name']),
                'description' => trim($validated['description'] ?? '') ?: null,
                'status' => $validated['status'] ? 'active' : 'inactive',
            ];

            if ($this->categoryId !== null) {
                $category = $this->ownedCategories()
                    ->findOrFail($this->categoryId);

                $category->update($data);
                $message = 'Categoría actualizada correctamente.';
            } else {
                Category::create($data);
                $message = 'Categoría creada correctamente.';
            }

            $this->showForm = false;
            $this->categoryId = null;
            $this->resetPage();

            session()->flash('category-message', $message);
        }

        public function deleteCategory(int $id): void
        {
            $category = $this->ownedCategories()->findOrFail($id);

            if ($category->products()->exists()) {
                session()->flash(
                    'category-error',
                    'No puedes eliminar una categoría que contiene productos.'
                );

                return;
            }

            $category->delete();

            session()->flash(
                'category-message',
                'Categoría eliminada correctamente.'
            );

            $this->resetPage();
        }

        public function with(): array
        {
            $search = trim($this->search);

            $categories = $this->ownedCategories()
                ->with('establishment')
                ->withCount('products')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when(
                    $this->filterEstablishment !== '',
                    function ($query) {
                        $query->where(
                            'establishment_id',
                            $this->filterEstablishment
                        );
                    }
                )
                ->orderBy('name')
                ->paginate(10);

            return [
                'categories' => $categories,
                'establishments' => Auth::user()
                    ->accessibleEstablishments('catalog')
                    ->orderBy('name')
                    ->get(),
            ];
        }
    };
?>

<div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
    <x-page-header title="Categorías" subtitle="Organiza los productos de tus establecimientos.">
        <x-ui.button wire:click="createCategory" icon="plus">Nueva categoría</x-ui.button>
    </x-page-header>

    @if (session('category-message'))
        <x-ui.alert>{{ session('category-message') }}</x-ui.alert>
    @endif

    @if (session('category-error'))
        <x-ui.alert tone="error">{{ session('category-error') }}</x-ui.alert>
    @endif

    @if ($showForm)
        <section class="nyva-card p-5 md:p-6">
            <x-ui.section-header :title="$categoryId ? 'Editar categoría' : 'Nueva categoría'" subtitle="Completa los datos de la categoría." class="mb-5" />

            @if ($establishments->isEmpty())
                <x-ui.alert tone="warning">Primero debes registrar un establecimiento para poder crear categorías.</x-ui.alert>
            @else
                <form wire:submit="save" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-ui.select label="Establecimiento" name="establishmentId" wire:model="establishmentId">
                        <option value="">Selecciona un establecimiento</option>
                        @foreach ($establishments as $establishment)
                            <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.input label="Nombre" name="name" wire:model="name" maxlength="100" placeholder="Ej. Bebidas, Postres, Entradas" />

                    <div class="md:col-span-2">
                        <label for="description" class="nyva-label">Descripción <span class="font-normal text-slate-400">(opcional)</span></label>
                        <textarea id="description" wire:model="description" rows="3" maxlength="5000" placeholder="Describe brevemente esta categoría..." class="nyva-input"></textarea>
                        @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-3 text-sm md:col-span-2">
                        <input type="checkbox" wire:model="status" class="size-4 rounded border-slate-300 text-blue-700">
                        <span>
                            <span class="block font-medium text-slate-700">Categoría activa</span>
                            <span class="block text-slate-500">Se muestra en la página pública.</span>
                        </span>
                    </label>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end md:col-span-2">
                        <x-ui.button variant="secondary" wire:click="cancelForm">Cancelar</x-ui.button>
                        <x-ui.button type="submit" target="save">{{ $categoryId ? 'Guardar cambios' : 'Crear categoría' }}</x-ui.button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    <section class="nyva-card overflow-hidden">
        <div class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-[1fr_16rem]">
            <x-ui.input name="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar categorías..." aria-label="Buscar categorías" />

            <x-ui.select name="filterEstablishment" wire:model.live="filterEstablishment" aria-label="Filtrar por establecimiento">
                <option value="">Todos los establecimientos</option>
                @foreach ($establishments as $establishment)
                    <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                @endforeach
            </x-ui.select>
        </div>

        @if ($categories->isEmpty())
            <x-ui.empty-state
                icon="archive-box"
                :title="$search !== '' || $filterEstablishment !== '' ? 'No se encontraron categorías' : 'Todavía no tienes categorías'"
                :description="$search !== '' || $filterEstablishment !== '' ? 'Prueba con otra búsqueda o cambia el filtro.' : 'Crea tu primera categoría para organizar tus productos.'"
            >
                @if ($search === '' && $filterEstablishment === '')
                    <x-ui.button wire:click="createCategory" icon="plus">Crear primera categoría</x-ui.button>
                @endif
            </x-ui.empty-state>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="nyva-table">
                    <thead>
                        <tr><th>Categoría</th><th>Establecimiento</th><th>Productos</th><th>Estado</th><th class="text-right">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr wire:key="category-{{ $category->id }}">
                                <td>
                                    <div class="font-semibold text-slate-800">{{ $category->name }}</div>
                                    @if ($category->description)
                                        <div class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $category->description }}</div>
                                    @endif
                                </td>
                                <td>{{ $category->establishment?->name ?? 'Sin establecimiento' }}</td>
                                <td><x-ui.badge>{{ $category->products_count }}</x-ui.badge></td>
                                <td><x-ui.badge :tone="$category->status === 'active' ? 'green' : 'slate'">{{ $category->status === 'active' ? 'Activa' : 'Inactiva' }}</x-ui.badge></td>
                                <td>
                                    <div class="flex justify-end gap-4 whitespace-nowrap">
                                        <button type="button" wire:click="editCategory({{ $category->id }})" class="nyva-link">Editar</button>
                                        <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="¿Seguro que deseas eliminar esta categoría?" wire:loading.attr="disabled" wire:target="deleteCategory({{ $category->id }})" class="nyva-link-danger">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">
                @foreach ($categories as $category)
                    <div wire:key="category-card-{{ $category->id }}" class="flex flex-col gap-2 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800">{{ $category->name }}</p>
                                <p class="text-xs text-slate-500">{{ $category->establishment?->name }} · {{ $category->products_count }} productos</p>
                            </div>
                            <x-ui.badge :tone="$category->status === 'active' ? 'green' : 'slate'">{{ $category->status === 'active' ? 'Activa' : 'Inactiva' }}</x-ui.badge>
                        </div>
                        <div class="flex gap-4 text-sm">
                            <button type="button" wire:click="editCategory({{ $category->id }})" class="nyva-link">Editar</button>
                            <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="¿Seguro que deseas eliminar esta categoría?" class="nyva-link-danger">Eliminar</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-100 p-4">{{ $categories->links() }}</div>
        @endif
    </section>
</div>
