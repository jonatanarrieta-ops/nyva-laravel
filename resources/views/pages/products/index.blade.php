<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\ImageStorage;
use App\Services\PlanLimits;
use Illuminate\Support\Facades\Auth;
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
            return Product::query()->whereHas('category', function ($query) {
                $query->whereIn('establishment_id', Auth::user()->accessibleEstablishments('catalog')->select('id'));
            });
        }

        private function ownedCategories()
        {
            return Category::query()->whereIn('establishment_id', Auth::user()->accessibleEstablishments('catalog')->select('id'));
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

            if ($this->productId !== null) {
                $product = $this->ownedProducts()->findOrFail($this->productId);

                if ($product->category->establishment_id !== $category->establishment_id) {
                    app(PlanLimits::class)->ensureCanAddProduct($category->establishment);
                }

                if ($this->image) {
                    $data['image'] = app(ImageStorage::class)->replace($this->image, 'products', $product->image);
                }

                $product->update($data);
                $message = 'Producto actualizado correctamente.';
            } else {
                app(PlanLimits::class)->ensureCanAddProduct($category->establishment);

                if ($this->image) {
                    $data['image'] = $this->image->store('products', 'public');
                }

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
            app(ImageStorage::class)->delete($image);

            session()->flash('product-message', 'Producto eliminado correctamente.');
            $this->resetPage();
        }

        public function removeImage(): void
        {
            $product = $this->ownedProducts()->findOrFail($this->productId);

            app(ImageStorage::class)->delete($product->image);
            $product->update(['image' => null]);
            $this->currentImage = null;
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
                ->accessibleEstablishments('catalog')
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

<div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
    <x-page-header title="Productos" subtitle="Administra los productos de tus establecimientos.">
        <x-ui.button wire:click="createProduct" icon="plus">Nuevo producto</x-ui.button>
    </x-page-header>

    @if (session('product-message'))
        <x-ui.alert>{{ session('product-message') }}</x-ui.alert>
    @endif

    @if (session('product-error'))
        <x-ui.alert tone="error">{{ session('product-error') }}</x-ui.alert>
    @endif

    @if ($showForm)
        <section class="nyva-card p-5 md:p-6">
            <x-ui.section-header :title="$productId ? 'Editar producto' : 'Nuevo producto'" subtitle="Completa la información que verán tus clientes." class="mb-5" />

            @if ($formCategories->isEmpty())
                <x-ui.alert tone="warning">Primero debes crear un establecimiento y al menos una categoría.</x-ui.alert>
            @else
                <form wire:submit="save" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <x-ui.select label="Categoría" name="categoryId" wire:model="categoryId">
                        <option value="">Selecciona una categoría</option>
                        @foreach ($formCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->establishment->name }} — {{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>

                    <x-ui.input label="Nombre" name="name" wire:model="name" maxlength="150" />

                    <x-ui.input label="Precio" name="price" type="number" min="0" step="0.01" wire:model="price" />

                    <div>
                        <label for="image" class="nyva-label">Imagen</label>

                        @if ($currentImage)
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ $currentImage }}" alt="Imagen actual" onerror="this.remove()" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
                                <button type="button" wire:click="removeImage" wire:confirm="¿Eliminar la imagen?" class="nyva-link-danger text-sm">Eliminar imagen</button>
                            </div>
                        @endif

                        <input id="image" type="file" wire:model="image" accept=".jpg,.jpeg,.png,.webp" class="nyva-input file:me-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-blue-700">
                        <p class="mt-1.5 text-xs text-slate-500">JPG, PNG o WEBP, máximo 2 MB.</p>
                        <p wire:loading wire:target="image" class="mt-1 text-sm text-blue-700">Cargando imagen...</p>
                        @error('image') <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="nyva-label">Descripción</label>
                        <textarea id="description" wire:model="description" rows="3" class="nyva-input"></textarea>
                        @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:gap-6 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="available" class="size-4 rounded border-slate-300 text-blue-700" />
                            Disponible para pedidos
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="status" class="size-4 rounded border-slate-300 text-blue-700" />
                            Visible en la página pública
                        </label>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end md:col-span-2">
                        <x-ui.button variant="secondary" wire:click="cancelForm">Cancelar</x-ui.button>
                        <x-ui.button type="submit" target="save, image">Guardar producto</x-ui.button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    <section class="nyva-card grid grid-cols-1 gap-3 p-4 md:grid-cols-3">
        <x-ui.input label="Buscar" name="search" type="search" wire:model.live.debounce.300ms="search" placeholder="Nombre o descripción..." />

        <x-ui.select label="Establecimiento" name="filterEstablishment" wire:model.live="filterEstablishment">
            <option value="">Todos los establecimientos</option>
            @foreach ($establishments as $establishment)
                <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.select label="Categoría" name="filterCategory" wire:model.live="filterCategory">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-ui.select>
    </section>

    @if ($products->isEmpty())
        <div class="nyva-card">
            <x-ui.empty-state icon="shopping-bag" title="No se encontraron productos" description="Crea un producto o cambia los filtros." />
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="search, filterEstablishment, filterCategory">
            @foreach ($products as $product)
                <article wire:key="product-{{ $product->id }}" class="nyva-card flex flex-col overflow-hidden">
                    <div class="relative flex h-36 items-center justify-center bg-slate-100 text-3xl font-semibold text-slate-300">
                        @if ($product->imageUrl())
                            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 size-full object-cover">
                        @endif
                        <span aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
                    </div>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-slate-800">{{ $product->name }}</h3>
                                <p class="truncate text-xs text-slate-500">{{ $product->category->name }} · {{ $product->category->establishment->name }}</p>
                            </div>
                            <p class="whitespace-nowrap font-bold text-slate-800">$ {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                        </div>

                        @if ($product->description)
                            <p class="line-clamp-2 text-sm text-slate-500">{{ $product->description }}</p>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge :tone="$product->available ? 'green' : 'slate'">{{ $product->available ? 'Disponible' : 'No disponible' }}</x-ui.badge>
                            <x-ui.badge :tone="$product->status === 'active' ? 'blue' : 'slate'">{{ $product->status === 'active' ? 'Activo' : 'Inactivo' }}</x-ui.badge>
                        </div>

                        <div class="mt-auto flex justify-end gap-4 border-t border-slate-100 pt-3 text-sm">
                            <button type="button" wire:click="editProduct({{ $product->id }})" class="nyva-link">Editar</button>
                            <button type="button" wire:click="deleteProduct({{ $product->id }})" wire:confirm="¿Seguro que deseas eliminar este producto?" class="nyva-link-danger">Eliminar</button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div>{{ $products->links() }}</div>
</div>
