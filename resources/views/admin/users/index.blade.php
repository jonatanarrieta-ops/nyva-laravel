@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Usuarios" subtitle="Administra las cuentas y sus permisos.">
            <a href="{{ route('admin.users.create') }}" class="nyva-btn">Nuevo usuario</a>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        @if (session('error'))
            <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
        @endif

        <section class="nyva-card overflow-hidden">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 p-4 sm:flex-row">
                <input type="search" name="search" value="{{ $search }}" placeholder="Buscar usuario..." aria-label="Buscar" class="nyva-input sm:max-w-xs">
                <select name="role" aria-label="Filtrar por rol" onchange="this.form.submit()" class="nyva-input sm:w-60">
                    <option value="">Todos los roles</option>
                    @foreach (\App\Models\User::ROLES as $value => $label)
                        <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="nyva-btn-secondary">Filtrar</button>
            </form>

            <div class="overflow-x-auto">
                <table class="nyva-table">
                    <thead>
                        <tr><th>Usuario</th><th>Rol</th><th>Estado</th><th class="text-right">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $user->first_name }} {{ $user->last_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $user->email }}</div>
                                </td>
                                <td>{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</td>
                                <td>{{ $user->isActive() ? 'Activo' : 'Inactivo' }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="nyva-link">Editar</a>
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('¿Cambiar el estado de esta cuenta?')" class="nyva-link-danger">
                                                {{ $user->isActive() ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4">{{ $users->links() }}</div>
        </section>
    </div>
@endsection
