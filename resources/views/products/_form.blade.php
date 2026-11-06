@csrf

<x-ui.select label="Categoría" name="category_id">
    <option value="">Selecciona una categoría</option>
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->establishment->name }} — {{ $category->name }}</option>
    @endforeach
</x-ui.select>

<x-ui.input label="Nombre" name="name" :value="old('name', $product?->name)" maxlength="150" />

<x-ui.input label="Precio" name="price" type="number" min="0" step="0.01" :value="old('price', $product?->price)" />

<div>
    <label for="image" class="nyva-label">Imagen</label>

    @if ($product?->imageUrl())
        <div class="mb-2 flex items-center gap-3">
            <img src="{{ $product->imageUrl() }}" alt="Imagen actual" onerror="this.remove()" class="size-16 rounded-lg object-cover ring-1 ring-slate-200">
            <label class="flex items-center gap-2 text-sm text-red-600">
                <input type="checkbox" name="remove_image" value="1" class="size-4 rounded border-slate-300">
                Eliminar imagen
            </label>
        </div>
    @endif

    <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="nyva-input file:me-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:font-semibold file:text-blue-700">
    <p class="mt-1.5 text-xs text-slate-500">JPG, PNG o WEBP, máximo 2 MB.</p>
    @error('image') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div class="md:col-span-2">
    <label for="description" class="nyva-label">Descripción</label>
    <textarea id="description" name="description" rows="3" class="nyva-input">{{ old('description', $product?->description) }}</textarea>
    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div class="flex flex-col gap-3 sm:flex-row sm:gap-6 md:col-span-2">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="available" value="0">
        <input type="checkbox" name="available" value="1" @checked(old('available', $product?->available ?? true)) class="size-4 rounded border-slate-300 text-blue-700" />
        Disponible para pedidos
    </label>
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="hidden" name="status" value="0">
        <input type="checkbox" name="status" value="1" @checked(old('status', ($product?->status ?? 'active') === 'active')) class="size-4 rounded border-slate-300 text-blue-700" />
        Visible en la página pública
    </label>
</div>

<div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end md:col-span-2">
    <x-ui.button variant="secondary" :href="route('products.index')">Cancelar</x-ui.button>
    <x-ui.button type="submit">Guardar producto</x-ui.button>
</div>
