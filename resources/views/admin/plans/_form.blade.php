@csrf

<div class="grid gap-5 md:grid-cols-3">
    <div class="md:col-span-3">
        <label for="name" class="nyva-label">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $plan?->name) }}" maxlength="100" class="nyva-input">
        @error('name') <p class="nyva-error">{{ $message }}</p> @enderror
    </div>

    @foreach (['monthly_price' => 'Precio mensual', 'annual_price' => 'Precio anual (opcional)', 'duration_days' => 'Duración en días', 'product_limit' => 'Límite de productos (vacío: sin límite)', 'user_limit' => 'Límite de usuarios (vacío: sin límite)'] as $field => $label)
        <div>
            <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}" type="number" min="0" step="any" value="{{ old($field, $plan?->{$field} ?? ($field === 'duration_days' ? 30 : null)) }}" class="nyva-input">
            @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
    @endforeach
</div>

<div>
    <label for="description" class="nyva-label">Descripción</label>
    <textarea id="description" name="description" rows="2" maxlength="2000" class="nyva-input">{{ old('description', $plan?->description) }}</textarea>
    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<fieldset>
    <legend class="nyva-label">Funcionalidades</legend>
    <div class="flex flex-wrap gap-4 text-sm">
        @foreach (\App\Models\Plan::FEATURES as $value => $label)
            <label class="flex items-center gap-2">
                <input type="checkbox" name="features[]" value="{{ $value }}" @checked(in_array($value, old('features', $plan?->features ?? []), true))>
                {{ $label }}
            </label>
        @endforeach
    </div>
    @error('features.*') <p class="nyva-error">{{ $message }}</p> @enderror
</fieldset>

<label class="flex items-center gap-2 text-sm">
    <input type="hidden" name="status" value="0">
    <input type="checkbox" name="status" value="1" @checked(old('status', ($plan?->status ?? 'active') === 'active'))>
    Plan activo
</label>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('admin.plans.index') }}" class="nyva-btn-secondary">Cancelar</a>
    <button type="submit" class="nyva-btn">Guardar</button>
</div>
