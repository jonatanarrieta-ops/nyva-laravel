@csrf

@foreach (['first_name' => 'Nombres', 'last_name' => 'Apellidos', 'email' => 'Correo'] as $field => $label)
    <div>
        <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
        <input id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, $user?->{$field}) }}" class="nyva-input">
        @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
    </div>
@endforeach

<div>
    <label for="password" class="nyva-label">Contraseña {{ $user ? '(déjala vacía para conservarla)' : '' }}</label>
    <input id="password" name="password" type="password" autocomplete="new-password" class="nyva-input">
    @error('password') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

@php($currentRole = old('role', $user ? ($user->role === 'admin' ? 'platform_admin' : $user->role) : 'establishment_admin'))

<div>
    <label for="role" class="nyva-label">Rol</label>
    <select id="role" name="role" class="nyva-input">
        @foreach (\App\Models\User::ROLES as $value => $label)
            <option value="{{ $value }}" @selected($currentRole === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('role') <p class="nyva-error">{{ $message }}</p> @enderror
</div>

<div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end md:col-span-2">
    <a href="{{ route('admin.users.index') }}" class="nyva-btn-secondary">Cancelar</a>
    <button type="submit" class="nyva-btn">Guardar</button>
</div>
