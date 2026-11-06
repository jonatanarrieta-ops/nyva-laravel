<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Establishment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $establishmentId = (string) $request->query('establishment');

        $categories = $this->ownedCategories()
            ->with('establishment')
            ->withCount('products')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($establishmentId !== '', fn (Builder $query) => $query->where('establishment_id', $establishmentId))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', [
            'title' => 'Categorías',
            'categories' => $categories,
            'establishments' => $this->establishments(),
            'search' => $search,
            'establishmentId' => $establishmentId,
        ]);
    }

    public function create(): View
    {
        return view('categories.create', [
            'title' => 'Nueva categoría',
            'establishments' => $this->establishments(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(string $id): View
    {
        return view('categories.edit', [
            'title' => 'Editar categoría',
            'category' => $this->ownedCategories()->findOrFail($id),
            'establishments' => $this->establishments(),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $category = $this->ownedCategories()->findOrFail($id);

        $category->update($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $category = $this->ownedCategories()->findOrFail($id);

        if ($category->products()->exists()) {
            return redirect()->route('categories.index')->with('error', 'No puedes eliminar una categoría que contiene productos.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Categoría eliminada correctamente.');
    }

    /**
     * @return array{establishment_id: int, name: string, description: string|null, status: string}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'establishment_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'boolean'],
        ], [
            'establishment_id.required' => 'Selecciona un establecimiento.',
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
        ]);

        $establishment = Auth::user()
            ->accessibleEstablishments('catalog')
            ->whereKey($data['establishment_id'])
            ->first();

        if (! $establishment) {
            throw ValidationException::withMessages([
                'establishment_id' => 'El establecimiento seleccionado no pertenece a tu cuenta.',
            ]);
        }

        return [
            'establishment_id' => $establishment->id,
            'name' => trim($data['name']),
            'description' => trim($data['description'] ?? '') ?: null,
            'status' => $request->boolean('status') ? 'active' : 'inactive',
        ];
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
     * @return Collection<int, Establishment>
     */
    private function establishments(): Collection
    {
        return Auth::user()->accessibleEstablishments('catalog')->orderBy('name')->get();
    }
}
