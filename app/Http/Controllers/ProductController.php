<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ImageStorage;
use App\Services\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(
        private ImageStorage $images,
        private PlanLimits $limits,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $establishmentId = (string) $request->query('establishment');
        $categoryId = (string) $request->query('category');

        $products = $this->ownedProducts()
            ->with('category.establishment')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($establishmentId !== '', fn (Builder $query) => $query->whereHas(
                'category',
                fn (Builder $category) => $category->where('establishment_id', $establishmentId),
            ))
            ->when($categoryId !== '', fn (Builder $query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'title' => 'Productos',
            'products' => $products,
            'establishments' => Auth::user()->accessibleEstablishments('catalog')->orderBy('name')->get(),
            'categories' => $this->ownedCategories()
                ->when($establishmentId !== '', fn (Builder $query) => $query->where('establishment_id', $establishmentId))
                ->orderBy('name')
                ->get(),
            'search' => $search,
            'establishmentId' => $establishmentId,
            'categoryId' => $categoryId,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'title' => 'Nuevo producto',
            'categories' => $this->formCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $category] = $this->validated($request);

        $this->limits->ensureCanAddProduct($category->establishment, 'category_id');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Producto creado correctamente.');
    }

    public function edit(string $id): View
    {
        return view('products.edit', [
            'title' => 'Editar producto',
            'product' => $this->ownedProducts()->findOrFail($id),
            'categories' => $this->formCategories(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $product = $this->ownedProducts()->with('category')->findOrFail($id);
        [$data, $category] = $this->validated($request);

        if ($product->category->establishment_id !== $category->establishment_id) {
            $this->limits->ensureCanAddProduct($category->establishment, 'category_id');
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->images->replace($request->file('image'), 'products', $product->image);
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($product->image);
            $data['image'] = null;
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $product = $this->ownedProducts()->findOrFail($id);

        if ($product->orderItems()->exists()) {
            return redirect()->route('products.index')->with('error', 'No puedes eliminar un producto que ya está asociado a pedidos.');
        }

        $image = $product->image;
        $product->delete();
        $this->images->delete($image);

        return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: Category}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'image' => ImageStorage::RULES,
            'available' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ], [
            'category_id.required' => 'Selecciona una categoría.',
            'name.required' => 'El nombre del producto es obligatorio.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'image.image' => ImageStorage::MESSAGES['image'],
            'image.mimes' => ImageStorage::MESSAGES['mimes'],
            'image.max' => ImageStorage::MESSAGES['max'],
        ]);

        $category = $this->ownedCategories()->with('establishment')->whereKey((int) $data['category_id'])->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'category_id' => 'La categoría seleccionada no pertenece a tu cuenta.',
            ]);
        }

        return [[
            'category_id' => $category->id,
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
            'price' => $data['price'],
            'available' => $request->boolean('available'),
            'status' => $request->boolean('status') ? 'active' : 'inactive',
        ], $category];
    }

    /**
     * @return Builder<Product>
     */
    private function ownedProducts(): Builder
    {
        return Product::query()->whereHas('category', function (Builder $query) {
            $query->whereIn('establishment_id', Auth::user()->accessibleEstablishments('catalog')->select('id'));
        });
    }

    /**
     * @return Builder<Category>
     */
    private function ownedCategories(): Builder
    {
        return Category::query()
            ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('catalog')->select('id'));
    }

    /**
     * @return Collection<int, Category>
     */
    private function formCategories(): Collection
    {
        return $this->ownedCategories()->with('establishment')->orderBy('name')->get();
    }
}
