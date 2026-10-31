<?php

namespace App\Services;

use App\Actions\PlaceOrder;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Carrito en sesión: guarda solo ids y cantidades, los precios siempre se leen de la base de datos.
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    public const ADDED = 'added';

    public const CONFLICT = 'conflict';

    public const UNAVAILABLE = 'unavailable';

    public function establishmentId(): ?int
    {
        return session(self::SESSION_KEY.'.establishment_id');
    }

    /**
     * @return array<int, int>
     */
    public function quantities(): array
    {
        return session(self::SESSION_KEY.'.items', []);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function add(Product $product): string
    {
        $product->loadMissing('category.establishment');

        if (! $product->isOrderable() || $product->category->establishment->status !== 'active') {
            return self::UNAVAILABLE;
        }

        $establishmentId = $product->category->establishment_id;

        if ($this->quantities() !== [] && $this->establishmentId() !== $establishmentId) {
            return self::CONFLICT;
        }

        $items = $this->quantities();
        $items[$product->id] = min(($items[$product->id] ?? 0) + 1, PlaceOrder::MAX_QUANTITY);

        session([self::SESSION_KEY => ['establishment_id' => $establishmentId, 'items' => $items]]);

        return self::ADDED;
    }

    public function increase(int $productId): void
    {
        $items = $this->quantities();

        if (isset($items[$productId])) {
            $items[$productId] = min($items[$productId] + 1, PlaceOrder::MAX_QUANTITY);
            $this->store($items);
        }
    }

    public function decrease(int $productId): void
    {
        $items = $this->quantities();

        if (! isset($items[$productId])) {
            return;
        }

        $items[$productId]--;

        if ($items[$productId] < 1) {
            unset($items[$productId]);
        }

        $this->store($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->quantities();
        unset($items[$productId]);

        $this->store($items);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, subtotal: float, orderable: bool}>
     */
    public function lines(): Collection
    {
        $quantities = $this->quantities();

        return Product::query()
            ->with('category')
            ->whereIn('id', array_keys($quantities))
            ->whereHas('category', fn ($query) => $query->where('establishment_id', $this->establishmentId()))
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'product' => $product,
                'quantity' => $quantities[$product->id],
                'subtotal' => round((float) $product->price * $quantities[$product->id], 2),
                'orderable' => $product->isOrderable(),
            ]);
    }

    /**
     * @param  array<int, int>  $items
     */
    private function store(array $items): void
    {
        if ($items === []) {
            $this->clear();

            return;
        }

        session([self::SESSION_KEY.'.items' => $items]);
    }
}
