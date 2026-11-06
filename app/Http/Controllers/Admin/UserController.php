<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = (string) $request->query('role');

        return view('admin.users.index', [
            'title' => 'Usuarios',
            'users' => User::query()
                ->when($search !== '', function (Builder $query) use ($search) {
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
                })
                ->when($role !== '', fn (Builder $query) => $query->where('role', $role))
                ->orderBy('first_name')
                ->paginate(15)
                ->withQueryString(),
            'search' => $search,
            'role' => $role,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['title' => 'Nuevo usuario']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = new User(['registered_at' => now()]);
        $user->forceFill([...$data, 'status' => 'active'])->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'title' => 'Editar usuario',
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->id === Auth::id() && $data['role'] !== 'platform_admin') {
            throw ValidationException::withMessages([
                'role' => 'No puedes quitarte tu propio acceso de administrador.',
            ]);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->forceFill($data)->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado.');
    }

    public function updateStatus(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->forceFill(['status' => $user->isActive() ? 'inactive' : 'active'])->save();

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ], [
            'first_name.required' => 'Los nombres son obligatorios.',
            'last_name.required' => 'Los apellidos son obligatorios.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'role.in' => 'El rol seleccionado no es válido.',
        ]);
    }
}
