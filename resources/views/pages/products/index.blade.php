<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts.app')]
    #[Title('Productos')]
    class extends Component {
        use WithFileUploads;
        use WithPagination;

        public string $search = '';
        public string $filterEstablishment = '';
        public string $filterCategory = '';

        public bool $showForm = false;
        public ?int $productId = null;

        public string $categoryId = '';
        public string $name = '';
        public string $description = '';
        public string $price = '';
        public bool $available = true;
        public bool $status = true;
        public $image = null;
        public ?string $currentImage = null;

        public function updatedSearch(): void
        {
            $this->resetPage();
        }

        public function updatedFilterEstablishment(): void
        {
            $this->filterCategory = '';
            $this->resetPage();
        }

        public function updatedFilterCategory(): void
        {
            $this->resetPage();
        }

        private function ownedProducts()
        {
            return Product::query()->whereHas('category.establishment', function ($query) {
                $query->where('user_id', Auth::id());
            });
        }

        private function ownedCategories()
        {
            return Category::query()->whereHas('establishment', function ($query) {
                $query->where('user_id', Auth::id());
            });
        }

        public function createProduct(): void
        {
            $this->resetValidation();
            $this->productId = null;
            $this->categoryId = '';
            $this->name = '';
            $this->description = '';
            $this->price = '';
            $this->available = true;
            $this->status = true;
            $this->image = null;
            $this->currentImage = null;
            $this->showForm = true;
        }

        public function editProduct(int $id): void
        {
            $product = $this->ownedProducts()->findOrFail($id);

            $this->resetValidation();
            $this->productId = $product->id;
            $this->categoryId = (string) $product->category_id;
            $this->name = $product->name;
            $this->description = $product->description ?? '';
            $this->price = (string) $product->price;
            $this->available = (bool) $product->available;
            $this->status = $product->status === 'active';
            $this->image = null;
            $this->currentImage = $product->imageUrl();
            $this->showForm = true;
        }

        public function cancelForm(): void
        {
            $this->showForm = false;
            $this->productId = null;
            $this->resetValidation();
        }

        public function save(): void
        {
            $validated = $this->validate([
                'categoryId' => ['required', 'integer'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string', 'max:5000'],
                'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'available' => ['required', 'boolean'],
                'status' => ['required', 'boolean'],
            ], [
                'categoryId.required' => 'Selecciona una categoría.',
                'name.required' => 'El nombre del producto es obligatorio.',
                'price.required' => 'El precio es obligatorio.',
                'price.numeric' => 'El precio debe ser un número válido.',
                'price.min' => 'El precio no puede ser negativo.',
                'image.image' => 'El archivo debe ser una imagen.',
                'image.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
                'image.max' => 'La imagen no puede superar los 2 MB.',
            ]);

            $category = $this->ownedCategories()
                ->find($validated['categoryId']);

            if (! $category) {
                $this->addError(
                    'categoryId',
                    'La categoría seleccionada no pertenece a tu cuenta.'
                );
                return;
            }

            $data = [
                'category_id' => $category->id,
                'name' => trim($validated['name']),
                'description' => trim($validated['description'] ?? '') ?: null,
                'price' => $validated['price'],
                'available' => $validated['available'],
                'status' => $validated['status'] ? 'active' : 'inactive',
            ];

            if ($this->image) {
                $data['image'] = $this->image->store('products', 'public');
            }

            if ($this->productId !== null) {
                $product = $this->ownedProducts()->findOrFail($this->productId);
                $previousImage = $product->image;
                $product->update($data);

                if ($this->image) {
                    $this->deleteStoredImage($previousImage);
                }

                $message = 'Producto actualizado correctamente.';
            } else {
                Product::create($data);
                $message = 'Producto creado correctamente.';
            }

            $this->showForm = false;
            $this->productId = null;
            $this->image = null;
            $this->resetPage();
            session()->flash('product-message', $message);
        }

        public function deleteProduct(int $id): void
        {
            $product = $this->ownedProducts()->findOrFail($id);

            if ($product->orderItems()->exists()) {
                session()->flash(
                    'product-error',
                    'No puedes eliminar un producto que ya está asociado a pedidos.'
                );
                return;
            }

            $image = $product->image;
            $product->delete();
            $this->deleteStoredImage($image);

            session()->flash('product-message', 'Producto eliminado correctamente.');
            $this->resetPage();
        }

        private function deleteStoredImage(?string $path): void
        {
            if ($path && ! str_starts_with($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }

        public function with(): array
        {
            $search = trim($this->search);

            $products = $this->ownedProducts()
                ->with('category.establishment')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($this->filterEstablishment !== '', function ($query) {
                    $query->whereHas('category', function ($categoryQuery) {
                        $categoryQuery->where(
                            'establishment_id',
                            $this->filterEstablishment
                        );
                    });
                })
                ->when($this->filterCategory !== '', function ($query) {
                    $query->where('category_id', $this->filterCategory);
                })
                ->orderBy('name')
                ->paginate(10);

            $establishments = Auth::user()
                ->establishments()
                ->orderBy('name')
                ->get();

            $categories = $this->ownedCategories()
                ->when($this->filterEstablishment !== '', function ($query) {
                    $query->where('establishment_id', $this->filterEstablishment);
                })
                ->orderBy('name')
                ->get();

            $formCategories = $this->ownedCategories()
                ->with('establishment')
                ->orderBy('name')
                ->get();

            return compact('products', 'establishments', 'categories', 'formCategories');
        }
    };
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Productos</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Administra los productos de tus establecimientos.
            </p>
        </div>

        <button
            type="button"
            wire:click="createProduct"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
        >
            <span>+</span> Nuevo producto
        </button>
    </div>

    @if (session('product-message'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('product-message') }}
        </div>
    @endif

    @if (session('product-error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            {{ session('product-error') }}
        </div>
    @endif

    @if ($showForm)
        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-5 text-lg font-semibold">
                {{ $productId ? 'Editar producto' : 'Nuevo producto' }}
            </h2>

            @if ($formCategories->isEmpty())
                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                    Primero debes crear un establecimiento y al menos una categoría.
                </div>
            @else
                <form wire:submit="save" class="flex flex-col gap-5">
                    <div>
                        <label for="categoryId" class="mb-1.5 block text-sm font-medium">Categoría</label>
                        <select
                            id="categoryId"
                            wire:model="categoryId"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        >
                            <option value="">Selecciona una categoría</option>
                            @foreach ($formCategories as $category)
                                <option value="{{ $category->id }}">
                                    {{ $category->establishment->name }} — {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('categoryId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium">Nombre</label>
                        <input
                            id="name"
                            type="text"
                            wire:model="name"
                            maxlength="150"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="mb-1.5 block text-sm font-medium">Descripción</label>
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        ></textarea>
                        @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="price" class="mb-1.5 block text-sm font-medium">Precio</label>
                        <input
                            id="price"
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model="price"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="image" class="mb-1.5 block text-sm font-medium">
                            Imagen <span class="font-normal text-zinc-400">(JPG, PNG o WEBP, máximo 2 MB)</span>
                        </label>

                        @if ($currentImage)
                            <img src="{{ $currentImage }}" alt="Imagen actual" onerror="this.remove()" class="mb-2 size-20 rounded-lg object-cover">
                        @endif

                        <input
                            id="image"
                            type="file"
                            wire:model="image"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                        />
                        <p wire:loading wire:target="image" class="mt-1 text-sm text-zinc-500">Cargando imagen...</p>
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="available" class="rounded border-zinc-300" />
                            Disponible para pedidos
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="status" class="rounded border-zinc-300" />
                            Activo
                        </label>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="cancelForm"
                            class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium dark:border-zinc-700"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900"
                        >
                            Guardar producto
                        </button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    <section class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div>
                <label for="search" class="mb-1.5 block text-sm font-medium">Buscar</label>
                <input
                    id="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nombre o descripción..."
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                />
            </div>

            <div>
                <label for="filterEstablishment" class="mb-1.5 block text-sm font-medium">Establecimiento</label>
                <select
                    id="filterEstablishment"
                    wire:model.live="filterEstablishment"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <option value="">Todos los establecimientos</option>
                    @foreach ($establishments as $establishment)
                        <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filterCategory" class="mb-1.5 block text-sm font-medium">Categoría</label>
                <select
                    id="filterCategory"
                    wire:model.live="filterCategory"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-950"
                >
                    <option value="">Todas las categorías</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700">
                    <tr>
                        <th class="px-3 py-3">Producto</th>
                        <th class="px-3 py-3">Establecimiento</th>
                        <th class="px-3 py-3">Categoría</th>
                        <th class="px-3 py-3">Precio</th>
                        <th class="px-3 py-3">Disponible</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($products as $product)
                        <tr>
                            <td class="px-3 py-3">
                                <div class="font-medium">{{ $product->name }}</div>
                                @if ($product->description)
                                    <div class="mt-1 max-w-xs truncate text-xs text-zinc-500">
                                        {{ $product->description }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-3">{{ $product->category->establishment->name }}</td>
                            <td class="px-3 py-3">{{ $product->category->name }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">${{ number_format((float) $product->price, 2, ',', '.') }}</td>
                            <td class="px-3 py-3">
                                @if ($product->available)
                                    <span class="text-emerald-600">Sí</span>
                                @else
                                    <span class="text-zinc-500">No</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($product->status === 'active')
                                    <span class="text-emerald-600">Activo</span>
                                @else
                                    <span class="text-zinc-500">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <button type="button" wire:click="editProduct({{ $product->id }})" class="font-medium text-blue-600 hover:underline">
                                        Editar
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deleteProduct({{ $product->id }})"
                                        wire:confirm="¿Seguro que deseas eliminar este producto?"
                                        class="font-medium text-red-600 hover:underline"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-10 text-center text-zinc-500">
                                No se encontraron productos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $products->links() }}
        </div>
    </section>
</div>
