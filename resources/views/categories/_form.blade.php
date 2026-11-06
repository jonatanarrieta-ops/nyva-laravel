@csrf

<x-ui.select label="Establecimiento" name="establishment_id">
    <option value="">Selecciona un establecimiento</option>
    @foreach ($establishments as $establishment)
        <option value="{{ $establishment->id }}" @selected((string) old('establishment_id', $category?->establishment_id) === (string) $establishment->id)>{{ $establishment->name }}</option>
    @endforeach
</x-ui.select>

<x-ui.input label="Nombre" name="name" :value="old('name', $category?->name)" maxlength="100" placeholder="Ej. Bebidas, Postres, Entradas" />

<div class="md:col-span-2">
    <label for="description" class="nyva-label">Descripción <span class="font-normal text-slate-400">(opcional)</span></label>
    <textarea id="description" name="description" rows="3" maxlength="5000" placeholder="Describe brevemente esta categoría..." class="nyva-input">{{ old('description', $category?->description) }}</textarea>
    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<label class="flex items-center gap-3 text-sm md:col-span-2">
    <input type="hidden" name="status" value="0">
    <input type="checkbox" name="status" value="1" @checked(old('status', ($category?->status ?? 'active') === 'active')) class="size-4 rounded border-slate-300 text-blue-700">
    <span>
        <span class="block font-medium text-slate-700">Categoría activa</span>
        <span class="block text-slate-500">Se muestra en la página pública.</span>
    </span>
</label>

<div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end md:col-span-2">
    <x-ui.button variant="secondary" :href="route('categories.index')">Cancelar</x-ui.button>
    <x-ui.button type="submit">{{ $category ? 'Guardar cambios' : 'Crear categoría' }}</x-ui.button>
</div>
