<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Models\Establishment;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $establishmentId = (string) $request->query('establishment');
        $status = (string) $request->query('status');

        $orders = $this->ownedOrders()
            ->with('establishment')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($establishmentId !== '', fn (Builder $query) => $query->where('establishment_id', $establishmentId))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->latest('ordered_at')
            ->paginate(10)
            ->withQueryString();

        return view('orders.index', [
            'title' => 'Pedidos',
            'orders' => $orders,
            'establishments' => $this->establishments(),
            'search' => $search,
            'establishmentId' => $establishmentId,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        $establishment = $request->filled('establishment')
            ? Auth::user()->accessibleEstablishments('orders')->findOrFail($request->query('establishment'))
            : null;

        return view('orders.create', [
            'title' => 'Nuevo pedido',
            'establishments' => $this->establishments(),
            'establishment' => $establishment,
            'products' => $establishment ? $this->availableProducts($establishment) : collect(),
        ]);
    }

    public function store(Request $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $data = $request->validate([
            'establishment_id' => ['required', 'integer'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:'.PlaceOrder::MAX_QUANTITY],
        ], [
            'establishment_id.required' => 'Selecciona un establecimiento.',
            'items.*.integer' => 'Las cantidades deben ser números enteros.',
            'items.*.min' => 'Las cantidades no pueden ser negativas.',
            'items.*.max' => 'Las cantidades no pueden superar '.PlaceOrder::MAX_QUANTITY.'.',
        ]);

        $establishment = Auth::user()->accessibleEstablishments('orders')->whereKey($data['establishment_id'])->first();

        if (! $establishment) {
            throw ValidationException::withMessages(['establishment_id' => 'El establecimiento seleccionado no es válido.']);
        }

        $quantities = collect($data['items'] ?? [])
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn (int $quantity) => $quantity > 0)
            ->all();

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => 'Debes agregar al menos un producto al pedido.']);
        }

        $order = $placeOrder->handle(
            $establishment->id,
            $quantities,
            [
                'customer_name' => trim((string) $request->input('customer_name')),
                'customer_phone' => trim((string) $request->input('customer_phone')),
                'delivery_address' => trim((string) $request->input('delivery_address')),
            ],
            channel: 'panel',
        );

        return redirect()->route('orders.show', $order->id)->with('success', 'Pedido creado correctamente.');
    }

    public function show(string $id): View
    {
        $order = $this->ownedOrders()->with(['establishment', 'orderItems.product'])->findOrFail($id);

        return view('orders.show', [
            'title' => "Pedido #{$order->id}",
            'order' => $order,
        ]);
    }

    public function update(Request $request, string $id, UpdateOrderStatus $updateStatus): RedirectResponse
    {
        $order = $this->ownedOrders()->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
        ]);

        if (! $updateStatus->handle($order, $data['status'])) {
            return back()->with('error', 'No se permite ese cambio de estado.');
        }

        return back()->with('success', 'Estado del pedido actualizado correctamente.');
    }

    /**
     * @return Builder<Order>
     */
    private function ownedOrders(): Builder
    {
        return Order::query()
            ->whereIn('establishment_id', Auth::user()->accessibleEstablishments('orders')->select('id'));
    }

    /**
     * @return Collection<int, Establishment>
     */
    private function establishments(): Collection
    {
        return Auth::user()->accessibleEstablishments('orders')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function availableProducts(Establishment $establishment): Collection
    {
        return Product::query()
            ->with('category')
            ->where('available', true)
            ->where('status', 'active')
            ->whereHas('category', fn (Builder $query) => $query->where('establishment_id', $establishment->id))
            ->orderBy('name')
            ->get();
    }
}
