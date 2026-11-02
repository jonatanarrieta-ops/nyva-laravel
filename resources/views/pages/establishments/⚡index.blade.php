<?php

use App\Actions\PlaceOrder;
use App\Concerns\WithEstablishmentContext;
use App\Models\Establishment;
use App\Services\ImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')]
    #[Title('Mi establecimiento')]
    class extends Component {
        use WithEstablishmentContext;
        use WithFileUploads;

        public string $name = '';
        public string $type = '';
        public string $description = '';
        public string $address = '';
        public string $phone = '';
        public string $email = '';
        public string $slug = '';
        public string $operating_status = 'open';
        public $logo = null;
        public $cover = null;

        protected function establishmentPermission(): string
        {
            return 'settings';
        }

        public function mount(): void
        {
            if (Auth::user()->accessibleEstablishments('settings')->exists()) {
                $this->fillForm();
            }
        }

        public function mountWithEstablishmentContext(): void
        {
            $query = Auth::user()->accessibleEstablishments('settings');

            $this->currentEstablishmentId = (clone $query)->whereKey(session('current_establishment_id'))->value('id')
                ?? $query->orderBy('name')->value('id');
        }

        public function updatedCurrentEstablishmentId(): void
        {
            session(['current_establishment_id' => $this->currentEstablishment()->id]);
            $this->resetValidation();
            $this->fillForm();
        }

        private function fillForm(): void
        {
            $establishment = $this->currentEstablishment();

            $this->name = $establishment->name;
            $this->type = $establishment->type;
            $this->description = $establishment->description ?? '';
            $this->address = $establishment->address;
            $this->phone = $establishment->phone;
            $this->email = $establishment->email ?? '';
            $this->slug = $establishment->slug;
            $this->operating_status = $establishment->operating_status;
            $this->logo = null;
            $this->cover = null;
        }

        public function save(ImageStorage $images): void
        {
            $establishment = $this->currentEstablishment();

            Gate::authorize('update', $establishment);

            $validated = $this->validate([
                'name' => ['required', 'string', 'max:150'],
                'type' => ['required', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:5000'],
                'address' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'regex:'.PlaceOrder::PHONE_REGEX],
                'email' => ['nullable', 'email', 'max:255'],
                'slug' => ['required', 'string', 'max:170', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/', Rule::unique(Establishment::class)->ignore($establishment->id)],
                'operating_status' => ['required', Rule::in(array_keys(Establishment::OPERATING_STATUSES))],
                'logo' => ImageStorage::RULES,
                'cover' => ImageStorage::RULES,
            ], [
                'name.required' => 'El nombre del establecimiento es obligatorio.',
                'type.required' => 'El tipo de establecimiento es obligatorio.',
                'address.required' => 'La dirección es obligatoria.',
                'phone.required' => 'El teléfono es obligatorio.',
                'phone.regex' => 'Ingresa un teléfono válido.',
                'email.email' => 'Ingresa un correo válido.',
                'slug.regex' => 'Usa solo minúsculas, números y guiones.',
                'slug.unique' => 'Este enlace ya está en uso.',
                'logo.image' => ImageStorage::MESSAGES['image'],
                'logo.mimes' => ImageStorage::MESSAGES['mimes'],
                'logo.max' => ImageStorage::MESSAGES['max'],
                'cover.image' => ImageStorage::MESSAGES['image'],
                'cover.mimes' => ImageStorage::MESSAGES['mimes'],
                'cover.max' => ImageStorage::MESSAGES['max'],
            ]);

            $establishment->slug = $validated['slug'];
            $establishment->fill([
                'name' => trim($validated['name']),
                'type' => trim($validated['type']),
                'description' => trim($validated['description'] ?? '') ?: null,
                'address' => trim($validated['address']),
                'phone' => trim($validated['phone']),
                'email' => trim($validated['email'] ?? '') ?: null,
                'operating_status' => $validated['operating_status'],
            ]);

            if ($this->logo) {
                $establishment->logo = $images->replace($this->logo, 'establishments', $establishment->logo);
            }

            if ($this->cover) {
                $establishment->cover_image = $images->replace($this->cover, 'establishments', $establishment->cover_image);
            }

            $establishment->save();

            $this->logo = null;
            $this->cover = null;

            session()->flash('notice', 'Establecimiento actualizado.');
        }

        public function removeImage(string $field, ImageStorage $images): void
        {
            abort_unless(in_array($field, ['logo', 'cover_image'], true), 422);

            $establishment = $this->currentEstablishment();

            Gate::authorize('update', $establishment);

            $images->delete($establishment->{$field});
            $establishment->forceFill([$field => null])->save();
        }

        public function with(): array
        {
            $establishment = $this->currentEstablishmentId ? $this->currentEstablishment() : null;

            return [
                'establishment' => $establishment,
                'establishments' => $this->establishmentOptions(),
                'plan' => $establishment?->currentSubscription(),
            ];
        }
    };
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Mi establecimiento" subtitle="Esta información se muestra en tu página pública.">
        <x-establishment-picker :establishments="$establishments" />

        @if ($establishment?->isPublic())
            <a href="{{ route('catalog.show', $establishment) }}" target="_blank" rel="noopener" class="nyva-btn-secondary">Ver página pública</a>
        @endif
    </x-page-header>

    <x-alerts />

    @if (! $establishment)
        <div class="nyva-alert-warning">
            Tu cuenta todavía no tiene un establecimiento asignado. El administrador de NYVA debe registrarlo.
        </div>
    @else
        @if ($establishment->status === 'suspended')
            <div class="nyva-alert-error">Este establecimiento está suspendido. Comunícate con el administrador de NYVA.</div>
        @elseif (! $plan)
            <div class="nyva-alert-warning">No tienes una suscripción vigente, así que no se reciben pedidos ni funciona el chatbot.</div>
        @endif

        <form wire:submit="save" class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="nyva-card flex flex-col gap-5 p-5 xl:col-span-2">
                <h2 class="font-semibold">Información del negocio</h2>

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach (['name' => 'Nombre comercial', 'type' => 'Tipo', 'address' => 'Dirección', 'phone' => 'Teléfono', 'email' => 'Correo (opcional)'] as $field => $label)
                        <div>
                            <label for="{{ $field }}" class="nyva-label">{{ $label }}</label>
                            <input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="nyva-input">
                            @error($field) <p class="nyva-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div>
                        <label for="operating_status" class="nyva-label">Estado de atención</label>
                        <select id="operating_status" wire:model="operating_status" class="nyva-input">
                            @foreach (Establishment::OPERATING_STATUSES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('operating_status') <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="description" class="nyva-label">Descripción (opcional)</label>
                    <textarea id="description" wire:model="description" rows="4" class="nyva-input"></textarea>
                    @error('description') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="nyva-label">Enlace público</label>
                    <div class="flex items-center gap-2">
                        <span class="hidden text-sm text-zinc-500 sm:block">/establecimiento/</span>
                        <input id="slug" type="text" wire:model="slug" class="nyva-input">
                    </div>
                    @error('slug') <p class="nyva-error">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="nyva-card flex flex-col gap-5 p-5">
                <h2 class="font-semibold">Imágenes</h2>

                @foreach ([['logo', 'logo', 'Logotipo', $establishment->logoUrl()], ['cover', 'cover_image', 'Imagen de portada', $establishment->coverUrl()]] as [$input, $column, $label, $url])
                    <div>
                        <label for="{{ $input }}" class="nyva-label">{{ $label }}</label>

                        @if ($url)
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ $url }}" alt="{{ $label }}" class="h-16 w-24 rounded-lg object-cover">
                                <button type="button" wire:click="removeImage('{{ $column }}')" wire:confirm="¿Eliminar esta imagen?" class="nyva-link-danger text-sm">Eliminar</button>
                            </div>
                        @endif

                        <input id="{{ $input }}" type="file" wire:model="{{ $input }}" accept=".jpg,.jpeg,.png,.webp" class="nyva-input">
                        <p wire:loading wire:target="{{ $input }}" class="mt-1 text-sm text-zinc-500">Cargando imagen...</p>
                        @error($input) <p class="nyva-error">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <button type="submit" wire:loading.attr="disabled" wire:target="save, logo, cover" class="nyva-btn mt-auto">
                    <span wire:loading.remove wire:target="save">Guardar cambios</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>
            </section>
        </form>
    @endif
</div>
