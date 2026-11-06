@csrf

<div class="grid gap-5 md:grid-cols-2">
    @foreach (['name' => 'Nombre', 'type' => 'Tipo (restaurante, hotel, cocina oculta)', 'address' => 'Dirección', 'phone' => 'Teléfono', 'email' => 'Correo (opcional)'] as $field => $label)
        <div>
            <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
            <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $establishment?->{$field}) }}" class="nyva-input">
            @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
    @endforeach
</div>

<div>
    <label for="description" class="nyva-label">Descripción (opcional)</label>
    <textarea id="description" name="description" rows="3" class="nyva-input">{{ old('description', $establishment?->description) }}</textarea>
    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

@php($ownerMode = old('owner_mode', $establishment ? 'existing' : 'new'))

<fieldset class="flex flex-col gap-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
    <legend class="font-semibold">Propietario</legend>

    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="radio" name="owner_mode" value="existing" @checked($ownerMode === 'existing')>
        Usar cuenta existente
    </label>

    <div class="ps-6">
        <label for="owner_id" class="nyva-label">Propietario</label>
        <select id="owner_id" name="owner_id" class="nyva-input">
            <option value="">Selecciona un propietario</option>
            @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected((string) old('owner_id', $establishment?->user_id) === (string) $owner->id)>{{ $owner->first_name }} {{ $owner->last_name }} ({{ $owner->email }})</option>
            @endforeach
        </select>
        @error('owner_id') <p class="nyva-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm font-medium">
        <input type="radio" name="owner_mode" value="new" @checked($ownerMode === 'new')>
        Crear cuenta nueva
    </label>

    <div class="grid gap-5 ps-6 md:grid-cols-2">
        <div>
            <label for="owner_first_name" class="nyva-label">Nombres</label>
            <input id="owner_first_name" name="owner_first_name" type="text" value="{{ old('owner_first_name') }}" class="nyva-input">
            @error('owner_first_name') <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="owner_last_name" class="nyva-label">Apellidos</label>
            <input id="owner_last_name" name="owner_last_name" type="text" value="{{ old('owner_last_name') }}" class="nyva-input">
            @error('owner_last_name') <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="owner_email" class="nyva-label">Correo de acceso</label>
            <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" class="nyva-input">
            @error('owner_email') <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="owner_password" class="nyva-label">Contraseña inicial</label>
            <input id="owner_password" name="owner_password" type="password" autocomplete="new-password" class="nyva-input">
            @error('owner_password') <p class="nyva-error">{{ $message }}</p> @enderror
        </div>
    </div>
</fieldset>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('admin.establishments.index') }}" class="nyva-btn-secondary">Cancelar</a>
    <button type="submit" class="nyva-btn">Guardar</button>
</div>
