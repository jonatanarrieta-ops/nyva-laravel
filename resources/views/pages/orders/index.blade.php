<?php

use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Pedidos')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $establishmentFilter = '';
    public string $statusFilter = '';

    public bool $showCreateForm = false;
    public ?int $selectedOrderId = null;

    public ?int $establishment_id = null;
    public string $customer_name = '';
    public string $customer_phone = '';
    public string $delivery_address = '';

    public array $cart = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstablishmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetValidation();

        $this->showCreateForm = true;
        $this->selectedOrderId = null;

        $this->establishment_id = null;
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->delivery_address = '';
        $this->cart = [];
    }

    public function closeCreateForm(): void
    {
        $this->resetValidation();

        $this->showCreateForm = false;

        $this->establishment_id = null;
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->delivery_address = '';
        $this->cart = [];
    }

    public function updatedEstablishmentId(): void
    {
        $this->cart = [];
    }

    public function addProduct(int $productId): void
    {
        $product = $this->availableProducts()
            ->firstWhere('id', $productId);

        if (!$product) {
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
            return;
        }

        $this->cart[$productId] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'quantity' => 1,
        ];
    }

    public function increaseQuantity(int $productId): void
    {
        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        }
    }

    public function decreaseQuantity(int $productId): void
    {
        if (!isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantity'] <= 1) {
            unset($this->cart[$productId]);
            return;
        }

        $this->cart[$productId]['quantity']--;
    }

    public function removeProduct(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(function ($item) {
            return ((float) $item['price']) * ((int) $item['quantity']);
        });
    }

    public function saveOrder(): void
    {
        $this->validate([
            'establishment_id' => ['required', 'integer'],
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'delivery_address' => ['required', 'string', 'max:255'],
        ], [
            'establishment_id.required' => 'Selecciona un establecimiento.',
            'customer_name.required' => 'El nombre del cliente es obligatorio.',
            'customer_phone.required' => 'El teléfono del cliente es obligatorio.',
            'delivery_address.required' => 'La dirección de entrega es obligatoria.',
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Debes agregar al menos un producto al pedido.');
            return;
        }

        $establishment = $this->userEstablishments()
            ->whereKey($this->establishment_id)
            ->first();

        if (!$establishment) {
            $this->addError('establishment_id', 'El establecimiento seleccionado no es válido.');
            return;
        }

        app(PlaceOrder::class)->handle(
            $establishment->id,
            collect($this->cart)->mapWithKeys(
                fn ($item) => [$item['product_id'] => $item['quantity']]
            )->all(),
            [
                'customer_name' => $this->customer_name,
                'customer_phone' => $this->customer_phone,
                'delivery_address' => $this->delivery_address,
            ],
            channel: 'panel',
        );

        session()->flash('success', 'Pedido creado correctamente.');

        $this->closeCreateForm();
        $this->resetPage();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('orders')->select('id'))
            ->firstOrFail();

        if (! app(UpdateOrderStatus::class)->handle($order, $status)) {
            session()->flash('error', 'No se permite ese cambio de estado.');
            return;
        }

        session()->flash('success', 'Estado del pedido actualizado correctamente.');
    }
    public function mount(): void
    {
        $orderId = request()->query('order');

        if ($orderId && ctype_digit((string) $orderId)) {
            $order = Order::query()
                ->whereKey((int) $orderId)
                ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('orders')->select('id'))
                ->exists();

            if ($order) {
                $this->selectedOrderId = (int) $orderId;
            }
        }
    }

    public function toggleDetails(int $orderId): void
    {
        $this->selectedOrderId = $this->selectedOrderId === $orderId
            ? null
            : $orderId;
    }

    private function userEstablishments()
    {
        return Auth::user()->accessibleEstablishments('orders');
    }

    private function availableProducts()
    {
        if (!$this->establishment_id) {
            return collect();
        }

        return Product::query()
            ->with('category')
            ->where('available', true)
            ->where('status', 'active')
            ->whereHas('category', function ($query) {
                $query->where('establishment_id', $this->establishment_id);
            })
            ->orderBy('name')
            ->get();
    }

    public function with(): array
    {
        $establishments = $this->userEstablishments()
            ->orderBy('name')
            ->get();

        $orders = Order::query()
            ->with([
                'establishment',
                'orderItems.product',
            ])
            ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('orders')->select('id'))
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', $search)
                        ->orWhere('customer_phone', 'like', $search);
                });
            })
            ->when($this->establishmentFilter !== '', function ($query) {
                $query->where('establishment_id', $this->establishmentFilter);
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest('ordered_at')
            ->paginate(10);

        $availableProducts = $this->availableProducts();

        return [
            'establishments' => $establishments,
            'orders' => $orders,
            'availableProducts' => $availableProducts,
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
    <x-page-header title="Pedidos" subtitle="Administra y registra los pedidos de tus establecimientos.">
        <x-ui.button wire:click="openCreateForm" icon="plus">Nuevo pedido</x-ui.button>
    </x-page-header>

    @if (session('success'))
        <x-ui.alert>{{ session('success') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="error">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($showCreateForm)
        <section class="nyva-card p-5 md:p-6">
            <x-ui.section-header title="Nuevo pedido" subtitle="Registra los datos del cliente y agrega los productos." class="mb-5">
                <button type="button" wire:click="closeCreateForm" class="nyva-link text-sm">Cancelar</button>
            </x-ui.section-header>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select label="Establecimiento" name="establishment_id" wire:model.live="establishment_id">
                    <option value="">Selecciona un establecimiento</option>
                    @foreach ($establishments as $establishment)
                        <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.input label="Nombre del cliente" name="customer_name" wire:model="customer_name" placeholder="Ej. Juan Pérez" />
                <x-ui.input label="Teléfono" name="customer_phone" type="tel" wire:model="customer_phone" placeholder="Ej. 3001234567" />
                <x-ui.input label="Dirección de entrega" name="delivery_address" wire:model="delivery_address" placeholder="Ej. Calle 10 # 20-30" />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-slate-800">Productos disponibles</h3>

                    @error('cart')
                        <x-ui.alert tone="error" class="mb-3">{{ $message }}</x-ui.alert>
                    @enderror

                    @if (! $establishment_id)
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            Selecciona un establecimiento para ver sus productos.
                        </div>
                    @elseif ($availableProducts->isEmpty())
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            No hay productos disponibles para este establecimiento.
                        </div>
                    @else
                        <div class="flex max-h-96 flex-col gap-2 overflow-y-auto pe-1">
                            @foreach ($availableProducts as $product)
                                <div wire:key="available-{{ $product->id }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-800">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $product->category?->name ?? 'Sin categoría' }} · $ {{ number_format((float) $product->price, 0, ',', '.') }}</p>
                                    </div>

                                    <x-ui.button variant="secondary" wire:click="addProduct({{ $product->id }})" class="shrink-0 px-3!">Agregar</x-ui.button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-slate-800">Pedido</h3>

                    @if (empty($cart))
                        <div class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                            Todavía no has agregado productos.
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach ($cart as $item)
                                <div wire:key="cart-{{ $item['product_id'] }}" class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-800">{{ $item['name'] }}</p>
                                            <p class="text-xs text-slate-500">$ {{ number_format((float) $item['price'], 0, ',', '.') }} c/u</p>
                                        </div>
                                        <button type="button" wire:click="removeProduct({{ $item['product_id'] }})" class="nyva-link-danger text-xs">Quitar</button>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button type="button" wire:click="decreaseQuantity({{ $item['product_id'] }})" aria-label="Disminuir cantidad" class="flex size-9 items-center justify-center rounded-lg border border-slate-300 font-bold text-slate-700 hover:bg-slate-50">−</button>
                                            <span class="w-8 text-center text-sm font-semibold">{{ $item['quantity'] }}</span>
                                            <button type="button" wire:click="increaseQuantity({{ $item['product_id'] }})" aria-label="Aumentar cantidad" class="flex size-9 items-center justify-center rounded-lg border border-slate-300 font-bold text-slate-700 hover:bg-slate-50">+</button>
                                        </div>
                                        <span class="font-semibold text-slate-800">$ {{ number_format((float) $item['price'] * (int) $item['quantity'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4">
                            <span class="font-semibold text-slate-800">Total</span>
                            <span class="text-xl font-bold text-slate-800">$ {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <x-ui.button variant="secondary" wire:click="closeCreateForm">Cancelar</x-ui.button>
                <x-ui.button wire:click="saveOrder" target="saveOrder">Crear pedido</x-ui.button>
            </div>
        </section>
    @endif

    <section class="nyva-card grid grid-cols-1 gap-3 p-4 md:grid-cols-3">
        <x-ui.input label="Buscar cliente" name="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Nombre o teléfono" />

        <x-ui.select label="Establecimiento" name="establishmentFilter" wire:model.live="establishmentFilter">
            <option value="">Todos</option>
            @foreach ($establishments as $establishment)
                <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
            @endforeach
        </x-ui.select>

        <x-ui.select label="Estado" name="statusFilter" wire:model.live="statusFilter">
            <option value="">Todos</option>
            @foreach (Order::STATUS_LABELS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </x-ui.select>
    </section>

    <section class="nyva-card overflow-hidden" wire:loading.class="opacity-60" wire:target="search, establishmentFilter, statusFilter">
        @if ($orders->isEmpty())
            <x-ui.empty-state icon="clipboard-document-list" title="No hay pedidos para mostrar" description="Cambia los filtros o registra un pedido nuevo." />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($orders as $order)
                    @php
                        $availableTransitions = Order::TRANSITIONS[$order->status] ?? [];
                        $open = $selectedOrderId === $order->id;
                    @endphp

                    <article wire:key="order-{{ $order->id }}" @class(['bg-blue-50/40' => $open])>
                        <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_7rem_minmax(0,1.2fr)_auto] md:items-center md:gap-4 md:px-5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800">#{{ $order->id }} · {{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->customer_phone }} · {{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}</p>
                            </div>

                            <p class="truncate text-sm text-slate-600">{{ $order->establishment?->name ?? '—' }}</p>

                            <p class="font-semibold text-slate-800">$ {{ number_format((float) $order->total, 0, ',', '.') }}</p>

                            <div class="flex flex-wrap items-center gap-2">
                                <x-order-status :status="$order->status" :label="$order->statusLabel()" />

                                @if (count($availableTransitions) > 0)
                                    <select
                                        wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                        wire:loading.attr="disabled"
                                        aria-label="Cambiar estado del pedido {{ $order->id }}"
                                        class="nyva-input w-auto! py-1.5! text-xs"
                                    >
                                        <option value="">Cambiar estado...</option>
                                        @foreach ($availableTransitions as $value)
                                            <option value="{{ $value }}">{{ Order::STATUS_LABELS[$value] }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <button type="button" wire:click="toggleDetails({{ $order->id }})" class="nyva-link justify-self-start text-sm md:justify-self-end">
                                {{ $open ? 'Ocultar' : 'Ver detalle' }}
                            </button>
                        </div>

                        @if ($open)
                            <div class="grid grid-cols-1 gap-4 border-t border-blue-100 px-4 py-4 md:grid-cols-2 md:px-5">
                                <div>
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800">Datos de entrega</h3>
                                    <p class="text-sm text-slate-600">{{ $order->customer_name }} · {{ $order->customer_phone }}</p>
                                    <p class="text-sm text-slate-600">{{ $order->delivery_address ?: 'Sin dirección de entrega' }}</p>
                                </div>

                                <div>
                                    <h3 class="mb-2 text-sm font-semibold text-slate-800">Productos</h3>

                                    <div class="flex flex-col gap-1.5">
                                        @foreach ($order->orderItems as $item)
                                            <div class="flex justify-between gap-4 text-sm">
                                                <span class="text-slate-700">{{ $item->product?->name ?? 'Producto eliminado' }} × {{ $item->quantity }}</span>
                                                <span class="font-medium text-slate-800">$ {{ number_format((float) $item->subtotal, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($orders->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </section>
</div>
