<?php

use App\Actions\PlaceOrder;
use App\Models\Establishment;
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
            ]
        );

        session()->flash('success', 'Pedido creado correctamente.');

        $this->closeCreateForm();
        $this->resetPage();
    }

    public function updateStatus(int $orderId, string $status): void
    {
        $order = Order::query()
            ->whereKey($orderId)
            ->whereHas('establishment', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->firstOrFail();

        if (! $order->canTransitionTo($status)) {
            session()->flash('error', 'No se permite ese cambio de estado.');
            return;
        }

        $order->update([
            'status' => $status,
        ]);

        session()->flash('success', 'Estado del pedido actualizado correctamente.');
    }
    public function mount(): void
    {
        $orderId = request()->query('order');

        if ($orderId && ctype_digit((string) $orderId)) {
            $order = Order::query()
                ->whereKey((int) $orderId)
                ->whereHas('establishment', function ($query) {
                    $query->where('user_id', Auth::id());
                })
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
        return Establishment::query()
            ->where('user_id', Auth::id());
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
            ->whereHas('establishment', function ($query) {
                $query->where('user_id', Auth::id());
            })
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

<div class="space-y-6">

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pedidos</h1>
            <p class="mt-1 text-sm text-gray-500">
                Administra y registra los pedidos de tus establecimientos.
            </p>
        </div>

        <button
            type="button"
            wire:click="openCreateForm"
            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
        >
            + Nuevo pedido
        </button>
    </div>

    @if ($showCreateForm)
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Nuevo pedido</h2>
                    <p class="text-sm text-gray-500">
                        Registra los datos del cliente y agrega los productos.
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700"
                >
                    Cancelar
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Establecimiento
                    </label>

                    <select
                        wire:model.live="establishment_id"
                        class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                        <option value="">Selecciona un establecimiento</option>

                        @foreach ($establishments as $establishment)
                            <option value="{{ $establishment->id }}">
                                {{ $establishment->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('establishment_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Nombre del cliente
                    </label>

                    <input
                        type="text"
                        wire:model="customer_name"
                        class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        placeholder="Ej. Juan Pérez"
                    >

                    @error('customer_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        wire:model="customer_phone"
                        class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        placeholder="Ej. 3001234567"
                    >

                    @error('customer_phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Dirección de entrega
                    </label>

                    <input
                        type="text"
                        wire:model="delivery_address"
                        class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        placeholder="Ej. Calle 10 # 20-30"
                    >

                    @error('delivery_address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">
                        Productos disponibles
                    </h3>

                    @error('cart')
                        <div class="mb-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ $message }}
                        </div>
                    @enderror

                    @if (!$establishment_id)
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Selecciona un establecimiento para ver sus productos.
                        </div>
                    @elseif ($availableProducts->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            No hay productos disponibles para este establecimiento.
                        </div>
                    @else
                        <div class="space-y-2">
                            @foreach ($availableProducts as $product)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900">
                                            {{ $product->name }}
                                        </p>

                                        <p class="text-xs text-gray-500">
                                            {{ $product->category?->name ?? 'Sin categoría' }}
                                        </p>

                                        <p class="mt-1 text-sm font-semibold text-gray-700">
                                            ${{ number_format((float) $product->price, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="addProduct({{ $product->id }})"
                                        class="ml-4 rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-200"
                                    >
                                        Agregar
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">
                        Pedido
                    </h3>

                    @if (empty($cart))
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Todavía no has agregado productos.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($cart as $item)
                                <div class="rounded-lg border border-gray-200 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900">
                                                {{ $item['name'] }}
                                            </p>

                                            <p class="text-sm text-gray-500">
                                                ${{ number_format((float) $item['price'], 0, ',', '.') }}
                                                c/u
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="removeProduct({{ $item['product_id'] }})"
                                            class="text-xs font-medium text-red-600 hover:text-red-700"
                                        >
                                            Quitar
                                        </button>
                                    </div>

                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                wire:click="decreaseQuantity({{ $item['product_id'] }})"
                                                class="h-8 w-8 rounded-lg bg-gray-100 text-sm font-bold text-gray-700 hover:bg-gray-200"
                                            >
                                                -
                                            </button>

                                            <span class="w-8 text-center text-sm font-semibold">
                                                {{ $item['quantity'] }}
                                            </span>

                                            <button
                                                type="button"
                                                wire:click="increaseQuantity({{ $item['product_id'] }})"
                                                class="h-8 w-8 rounded-lg bg-gray-100 text-sm font-bold text-gray-700 hover:bg-gray-200"
                                            >
                                                +
                                            </button>
                                        </div>

                                        <span class="font-semibold text-gray-900">
                                            ${{ number_format((float) $item['price'] * (int) $item['quantity'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-gray-200 pt-4">
                            <span class="font-semibold text-gray-900">Total</span>

                            <span class="text-xl font-bold text-gray-900">
                                ${{ number_format($this->cartTotal, 0, ',', '.') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-5">
                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    wire:click="saveOrder"
                    wire:loading.attr="disabled"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="saveOrder">
                        Crear pedido
                    </span>

                    <span wire:loading wire:target="saveOrder">
                        Guardando...
                    </span>
                </button>
            </div>
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">
                    Buscar cliente
                </label>

                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Nombre o teléfono"
                    class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">
                    Establecimiento
                </label>

                <select
                    wire:model.live="establishmentFilter"
                    class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    <option value="">Todos</option>

                    @foreach ($establishments as $establishment)
                        <option value="{{ $establishment->id }}">
                            {{ $establishment->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">
                    Estado
                </label>

                <select
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-gray-400 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 shadow-sm placeholder:text-gray-500 focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    <option value="">Todos</option>
                    <option value="pending">Pendiente</option>
                    <option value="confirmed">Confirmado</option>
                    <option value="preparing">Preparando</option>
                    <option value="shipped">Enviado</option>
                    <option value="delivered">Entregado</option>
                    <option value="cancelled">Cancelado</option>
                </select>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Cliente
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Establecimiento
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Total
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Estado
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Fecha
                        </th>

                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Acción
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-4">
                                <div class="font-medium text-gray-900">
                                    {{ $order->customer_name }}
                                </div>

                                <div class="text-sm text-gray-500">
                                    {{ $order->customer_phone }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-700">
                                {{ $order->establishment?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-4 text-sm font-semibold text-gray-900">
                                ${{ number_format((float) $order->total, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $order->statusLabel() }}
                                    </span>

                                    @php
                                        $availableTransitions = Order::TRANSITIONS[$order->status] ?? [];
                                    @endphp

                                    @if (count($availableTransitions) > 0)
                                        <select
                                            wire:change="updateStatus({{ $order->id }}, $event.target.value)"
                                            class="block w-full rounded-lg border border-gray-400 bg-gray-50 px-2 py-2 text-xs text-gray-900 shadow-sm focus:border-indigo-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                        >
                                            <option value="">Cambiar estado...</option>
                                            @foreach ($availableTransitions as $value)
                                                <option value="{{ $value }}">{{ Order::STATUS_LABELS[$value] }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-500">
                                {{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td class="px-4 py-4 text-right">
                                <button
                                    type="button"
                                    wire:click="toggleDetails({{ $order->id }})"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                                >
                                    {{ $selectedOrderId === $order->id ? 'Ocultar' : 'Ver detalle' }}
                                </button>
                            </td>
                        </tr>

                        @if ($selectedOrderId === $order->id)
                            <tr>
                                <td colspan="6" class="bg-gray-50 px-4 py-5">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <h3 class="mb-2 text-sm font-semibold text-gray-900">
                                                Datos de entrega
                                            </h3>

                                            <p class="text-sm text-gray-600">
                                                {{ $order->delivery_address ?: 'Sin dirección de entrega' }}
                                            </p>
                                        </div>

                                        <div>
                                            <h3 class="mb-2 text-sm font-semibold text-gray-900">
                                                Productos
                                            </h3>

                                            <div class="space-y-2">
                                                @foreach ($order->orderItems as $item)
                                                    <div class="flex justify-between gap-4 text-sm">
                                                        <span class="text-gray-700">
                                                            {{ $item->product?->name ?? 'Producto eliminado' }}
                                                            × {{ $item->quantity }}
                                                        </span>

                                                        <span class="font-medium text-gray-900">
                                                            ${{ number_format((float) $item->subtotal, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                No hay pedidos para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>




