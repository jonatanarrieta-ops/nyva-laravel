<?php

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Pedidos')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filterEstablishment = '';
    public string $filterStatus = '';

    public ?int $selectedOrderId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEstablishment(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function toggleDetails(int $id): void
    {
        $order = $this->ownedOrders()->whereKey($id)->firstOrFail();

        $this->selectedOrderId = $this->selectedOrderId === $order->id
            ? null
            : $order->id;
    }

    private function ownedOrders()
    {
        return Order::query()
            ->whereHas('establishment', function ($query) {
                $query->where('user_id', Auth::id());
            });
    }

    public function with(): array
    {
        $orders = $this->ownedOrders()
            ->with(['establishment', 'orderItems.product'])
            ->when($this->search !== '', function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($subquery) use ($search) {
                    $subquery->where('customer_name', 'like', $search)
                        ->orWhere('customer_phone', 'like', $search);
                });
            })
            ->when($this->filterEstablishment !== '', function ($query) {
                $query->where('establishment_id', $this->filterEstablishment);
            })
            ->when($this->filterStatus !== '', function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->orderByDesc('ordered_at')
            ->paginate(10);

        $establishments = Auth::user()
            ->establishments()
            ->orderBy('name')
            ->get();

        $selectedOrder = $this->selectedOrderId
            ? $this->ownedOrders()
                ->with(['establishment', 'orderItems.product'])
                ->whereKey($this->selectedOrderId)
                ->first()
            : null;

        return compact('orders', 'establishments', 'selectedOrder');
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight">Pedidos</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Consulta los pedidos de tus establecimientos.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <div class="md:col-span-1">
            <label for="orderSearch" class="mb-1 block text-sm font-medium">Buscar cliente</label>
            <input
                id="orderSearch"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Nombre o teléfono"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
        </div>

        <div>
            <label for="orderEstablishment" class="mb-1 block text-sm font-medium">Establecimiento</label>
            <select
                id="orderEstablishment"
                wire:model.live="filterEstablishment"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
                <option value="">Todos los establecimientos</option>
                @foreach ($establishments as $establishment)
                    <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="orderStatus" class="mb-1 block text-sm font-medium">Estado</label>
            <select
                id="orderStatus"
                wire:model.live="filterStatus"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm dark:border-zinc-700 dark:bg-zinc-900"
            >
                <option value="">Todos los estados</option>
                <option value="pending">Pendiente</option>
                <option value="confirmed">Confirmado</option>
                <option value="preparing">En preparación</option>
                <option value="ready">Listo</option>
                <option value="completed">Completado</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 font-medium">Pedido</th>
                    <th class="px-4 py-3 font-medium">Cliente</th>
                    <th class="px-4 py-3 font-medium">Establecimiento</th>
                    <th class="px-4 py-3 font-medium">Fecha</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Estado</th>
                    <th class="px-4 py-3 text-right font-medium">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="whitespace-nowrap px-4 py-3">#{{ $order->id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $order->customer_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->customer_phone }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $order->establishment->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $order->ordered_at?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            ${{ number_format((float) $order->total, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusLabels = [
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmado',
                                    'preparing' => 'En preparación',
                                    'ready' => 'Listo',
                                    'completed' => 'Completado',
                                    'cancelled' => 'Cancelado',
                                ];
                            @endphp
                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                wire:click="toggleDetails({{ $order->id }})"
                                class="font-medium text-blue-600 hover:underline"
                            >
                                {{ $selectedOrderId === $order->id ? 'Ocultar' : 'Ver pedido' }}
                            </button>
                        </td>
                    </tr>

                    @if ($selectedOrderId === $order->id && $selectedOrder)
                        <tr wire:key="order-detail-{{ $order->id }}">
                            <td colspan="7" class="bg-zinc-50 px-4 py-4 dark:bg-zinc-800/50">
                                <h3 class="mb-3 font-semibold">Detalle del pedido #{{ $order->id }}</h3>

                                @if ($selectedOrder->delivery_address)
                                    <p class="mb-3 text-sm">
                                        <strong>Dirección:</strong> {{ $selectedOrder->delivery_address }}
                                    </p>
                                @endif

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-zinc-500">
                                                <th class="py-2 pr-4">Producto</th>
                                                <th class="py-2 pr-4">Cantidad</th>
                                                <th class="py-2 pr-4">Precio unitario</th>
                                                <th class="py-2">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($selectedOrder->orderItems as $item)
                                                <tr class="border-t border-zinc-200 dark:border-zinc-700">
                                                    <td class="py-2 pr-4">{{ $item->product?->name ?? 'Producto eliminado' }}</td>
                                                    <td class="py-2 pr-4">{{ $item->quantity }}</td>
                                                    <td class="py-2 pr-4">${{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                                    <td class="py-2">${{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="py-3 text-zinc-500">Este pedido no tiene productos registrados.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <p class="mt-3 text-right font-semibold">
                                    Total: ${{ number_format((float) $selectedOrder->total, 2, ',', '.') }}
                                </p>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">
                            No se encontraron pedidos para los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $orders->links() }}
    </div>
</div>
