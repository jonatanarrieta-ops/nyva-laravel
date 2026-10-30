
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
                ->whereHas('establishment', function ($query) {
                    $query->where('user_id', Auth::id());
                });
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
            $this->status = (bool) $category->status;
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
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'status' => ['required', 'boolean'],
            ], [
                'establishmentId.required' => 'Selecciona un establecimiento.',
                'name.required' => 'El nombre de la categoría es obligatorio.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',
            ]);

            $establishment = Auth::user()
                ->establishments()
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
                'status' => $validated['status'],
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
                    ->establishments()
                    ->orderBy('name')
                    ->get(),
            ];
        }
    };
?>

<div class="flex flex-col gap-6 p-4 md:p-6">

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Categorías
            </h1>

            <p class="mt-1 text-sm text-zinc-500">
                Organiza los productos de tus establecimientos.
            </p>
        </div>

        <button
            type="button"
            wire:click="createCategory"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            <span>+</span>
            Nueva categoría
        </button>
    </div>

    @if (session('category-message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('category-message') }}
        </div>
    @endif

    @if (session('category-error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            {{ session('category-error') }}
        </div>
    @endif

    @if ($showForm)
        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold">
                    {{ $categoryId ? 'Editar categoría' : 'Nueva categoría' }}
                </h2>

                <p class="mt-1 text-sm text-zinc-500">
                    Completa los datos de la categoría.
                </p>
            </div>

            @if ($establishments->isEmpty())
                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    Primero debes registrar un establecimiento para poder crear categorías.
                </div>
            @else
                <form wire:submit="save" class="flex flex-col gap-5">

                    <div>
                        <label for="establishmentId" class="mb-1.5 block text-sm font-medium">
                            Establecimiento
                        </label>

                        <select
                            id="establishmentId"
                            wire:model="establishmentId"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="">Selecciona un establecimiento</option>

                            @foreach ($establishments as $establishment)
                                <option value="{{ $establishment->id }}">
                                    {{ $establishment->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('establishmentId')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">
                            Nombre
                        </label>

                        <input
                            id="name"
                            type="text"
                            wire:model="name"
                            maxlength="255"
                            placeholder="Ej. Bebidas, Postres, Entradas"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950"
                        >

                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-medium">
                            Descripción <span class="font-normal text-zinc-400">(opcional)</span>
                        </label>

                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            maxlength="5000"
                            placeholder="Describe brevemente esta categoría..."
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950"
                        ></textarea>

                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-3 text-sm">
                        <input
                            type="checkbox"
                            wire:model="status"
                            class="rounded border-zinc-300"
                        >

                        <span>
                            <span class="block font-medium">Categoría activa</span>
                            <span class="block text-zinc-500">Permite mantener la categoría habilitada.</span>
                        </span>
                    </label>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <button
                            type="button"
                            wire:click="cancelForm"
                            class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                        >
                            Cancelar
                        </button>

                        @if ($establishments->isNotEmpty())
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="save"
                                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900"
                            >
                                <span wire:loading.remove wire:target="save">
                                    {{ $categoryId ? 'Guardar cambios' : 'Crear categoría' }}
                                </span>

                                <span wire:loading wire:target="save">
                                    Guardando...
                                </span>
                            </button>
                        @endif
                    </div>
                </form>
            @endif
        </section>
    @endif

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">

        <div class="flex flex-col gap-3 border-b border-zinc-200 p-4 dark:border-zinc-700 sm:flex-row">
            <div class="flex-1">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar categorías..."
                    aria-label="Buscar categorías"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950"
                >
            </div>

            <div class="sm:w-64">
                <select
                    wire:model.live="filterEstablishment"
                    aria-label="Filtrar por establecimiento"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-zinc-500 dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <option value="">Todos los establecimientos</option>

                    @foreach ($establishments as $establishment)
                        <option value="{{ $establishment->id }}">
                            {{ $establishment->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-100 text-xl dark:bg-zinc-800">
                    #
                </div>

                <h3 class="font-semibold">
                    @if ($search !== '' || $filterEstablishment !== '')
                        No se encontraron categorías
                    @else
                        Todavía no tienes categorías
                    @endif
                </h3>

                <p class="mt-1 text-sm text-zinc-500">
                    @if ($search !== '' || $filterEstablishment !== '')
                        Prueba con otra búsqueda o cambia el filtro.
                    @else
                        Crea tu primera categoría para organizar tus productos.
                    @endif
                </p>

                @if ($search === '' && $filterEstablishment === '')
                    <button
                        type="button"
                        wire:click="createCategory"
                        class="mt-4 rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900"
                    >
                        Crear primera categoría
                    </button>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-800/60">
                        <tr>
                            <th class="px-5 py-3 font-medium">Categoría</th>
                            <th class="px-5 py-3 font-medium">Establecimiento</th>
                            <th class="px-5 py-3 font-medium">Productos</th>
                            <th class="px-5 py-3 font-medium">Estado</th>
                            <th class="px-5 py-3 text-right font-medium">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($categories as $category)
                            <tr wire:key="category-{{ $category->id }}" class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                                <td class="px-5 py-4">
                                    <div class="font-medium">
                                        {{ $category->name }}
                                    </div>

                                    @if ($category->description)
                                        <div class="mt-1 max-w-xs truncate text-xs text-zinc-500">
                                            {{ $category->description }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">
                                    {{ $category->establishment?->name ?? 'Sin establecimiento' }}
                                </td>

                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium dark:bg-zinc-800">
                                        {{ $category->products_count }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    @if ($category->status)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                            Activa
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            wire:click="editCategory({{ $category->id }})"
                                            class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800"
                                        >
                                            Editar
                                        </button>

                                        <button
                                            type="button"
                                            wire:click="deleteCategory({{ $category->id }})"
                                            wire:confirm="¿Seguro que deseas eliminar esta categoría?"
                                            wire:loading.attr="disabled"
                                            wire:target="deleteCategory({{ $category->id }})"
                                            class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 disabled:opacity-50 dark:border-red-900 dark:hover:bg-red-950"
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

            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $categories->links() }}
            </div>
        @endif
    </section>
</div>
