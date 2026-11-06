<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Models\Establishment;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EstablishmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status');

        return view('admin.establishments.index', [
            'title' => 'Establecimientos',
            'establishments' => Establishment::query()
                ->with('user')
                ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.establishments.create', [
            'title' => 'Nuevo establecimiento',
            'owners' => $this->owners(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $owner = $this->validatedOwner($request);

        DB::transaction(function () use ($data, $owner) {
            $establishment = new Establishment([...$data, 'registered_at' => now()]);
            $establishment->user_id = $this->ownerId($owner);
            $establishment->status = 'active';
            $establishment->save();
        });

        return redirect()->route('admin.establishments.index')->with('success', 'Establecimiento creado.');
    }

    public function edit(Establishment $establishment): View
    {
        return view('admin.establishments.edit', [
            'title' => 'Editar establecimiento',
            'establishment' => $establishment,
            'owners' => $this->owners(),
        ]);
    }

    public function update(Request $request, Establishment $establishment): RedirectResponse
    {
        $data = $this->validated($request);
        $owner = $this->validatedOwner($request);

        DB::transaction(function () use ($establishment, $data, $owner) {
            $establishment->fill($data);
            $establishment->user_id = $this->ownerId($owner);
            $establishment->save();
        });

        return redirect()->route('admin.establishments.index')->with('success', 'Establecimiento actualizado.');
    }

    public function updateStatus(Request $request, Establishment $establishment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Establishment::STATUSES))],
        ]);

        $establishment->forceFill(['status' => $data['status']])->save();

        if ($data['status'] === 'suspended') {
            $establishment->user?->notify(new AdminAlert(
                'Establecimiento suspendido',
                "{$establishment->name} fue suspendido y no recibirá pedidos.",
            ));
        }

        return back()->with('success', 'Estado actualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PlaceOrder::PHONE_REGEX],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'type.required' => 'El tipo es obligatorio.',
            'address.required' => 'La dirección es obligatoria.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.regex' => 'Ingresa un teléfono válido.',
        ]);

        return [
            ...$data,
            'email' => $data['email'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedOwner(Request $request): array
    {
        $mode = $request->input('owner_mode') === 'existing' ? 'existing' : 'new';

        $owner = $request->validate($mode === 'new'
            ? [
                'owner_first_name' => ['required', 'string', 'max:100'],
                'owner_last_name' => ['required', 'string', 'max:100'],
                'owner_email' => ['required', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'owner_password' => ['required', 'string', 'min:8'],
            ]
            : [
                'owner_id' => ['required', Rule::exists(User::class, 'id')->where('role', 'establishment_admin')],
            ], [
                'owner_first_name.required' => 'Los nombres del propietario son obligatorios.',
                'owner_last_name.required' => 'Los apellidos del propietario son obligatorios.',
                'owner_email.required' => 'El correo del propietario es obligatorio.',
                'owner_email.unique' => 'Ya existe una cuenta con ese correo.',
                'owner_password.required' => 'La contraseña inicial es obligatoria.',
                'owner_id.required' => 'Selecciona un propietario.',
            ]);

        return ['mode' => $mode, ...$owner];
    }

    /**
     * @param  array<string, mixed>  $owner
     */
    private function ownerId(array $owner): int
    {
        if ($owner['mode'] === 'existing') {
            return (int) $owner['owner_id'];
        }

        return User::forceCreate([
            'first_name' => $owner['owner_first_name'],
            'last_name' => $owner['owner_last_name'],
            'email' => $owner['owner_email'],
            'password' => $owner['owner_password'],
            'role' => 'establishment_admin',
            'status' => 'active',
            'registered_at' => now(),
        ])->id;
    }

    /**
     * @return Collection<int, User>
     */
    private function owners(): Collection
    {
        return User::where('role', 'establishment_admin')->orderBy('first_name')->get();
    }
}
