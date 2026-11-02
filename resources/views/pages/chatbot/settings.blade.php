<?php

use App\Concerns\WithEstablishmentContext;
use App\Services\Chatbot\ResponderFactory;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Chatbot')] class extends Component {
    use WithEstablishmentContext;

    public bool $enabled = true;
    public bool $orders_enabled = true;
    public string $welcome_message = '';
    public string $business_info = '';

    protected function establishmentPermission(): string
    {
        return 'settings';
    }

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function updatedCurrentEstablishmentId(): void
    {
        session(['current_establishment_id' => $this->currentEstablishment()->id]);
        $this->resetValidation();
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $settings = $this->currentEstablishment()->chatbot();

        $this->enabled = $settings->enabled;
        $this->orders_enabled = $settings->orders_enabled;
        $this->welcome_message = $settings->welcome_message ?? '';
        $this->business_info = $settings->business_info ?? '';
    }

    public function save(): void
    {
        $establishment = $this->currentEstablishment();

        Gate::authorize('update', $establishment);

        $data = $this->validate([
            'enabled' => ['boolean'],
            'orders_enabled' => ['boolean'],
            'welcome_message' => ['nullable', 'string', 'max:500'],
            'business_info' => ['nullable', 'string', 'max:3000'],
        ]);

        $establishment->chatbot()->update([
            ...$data,
            'welcome_message' => $data['welcome_message'] ?: null,
            'business_info' => $data['business_info'] ?: null,
        ]);

        session()->flash('notice', 'Chatbot actualizado.');
    }

    public function with(): array
    {
        $establishment = $this->currentEstablishment();

        return [
            'establishments' => $this->establishmentOptions(),
            'included' => $establishment->hasFeature('chatbot'),
            'aiEnabled' => ResponderFactory::aiConfigured(),
            'conversations' => $establishment->conversations()->count(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Chatbot" subtitle="Configura el asistente virtual de tu establecimiento.">
        <x-establishment-picker :establishments="$establishments" />
    </x-page-header>

    <x-alerts />

    @unless ($included)
        <div class="nyva-alert-warning">Tu plan actual no incluye el chatbot o no tienes una suscripción vigente.</div>
    @endunless

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <form wire:submit="save" class="nyva-card flex flex-col gap-5 p-5 xl:col-span-2">
            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" wire:model="enabled"> Chatbot habilitado</label>
            <label class="flex items-center gap-2 text-sm font-medium"><input type="checkbox" wire:model="orders_enabled"> Permitir pedidos por chat</label>

            <div>
                <label for="welcome_message" class="nyva-label">Mensaje de bienvenida</label>
                <input id="welcome_message" type="text" wire:model="welcome_message" placeholder="Ej. ¡Hola! ¿En qué puedo ayudarte?" class="nyva-input">
                @error('welcome_message') <p class="nyva-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="business_info" class="nyva-label">Información adicional del negocio</label>
                <textarea id="business_info" wire:model="business_info" rows="5" placeholder="Ej. Métodos de pago, zonas de entrega y promociones." class="nyva-input"></textarea>
                @error('business_info') <p class="nyva-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
            </div>
        </form>

        <div class="flex flex-col gap-4">
            <x-stat-card label="Conversaciones" :value="$conversations" hint="Con clientes de tu página pública" />

            <section class="nyva-card p-5 text-sm">
                <h2 class="font-semibold">Modo de respuesta</h2>
                <p class="mt-2 text-zinc-500">
                    {{ $aiEnabled
                        ? 'Inteligencia artificial activa, limitada a la información de tu negocio.'
                        : 'Respuestas automáticas con la información real de tu negocio.' }}
                </p>
            </section>
        </div>
    </div>
</div>
