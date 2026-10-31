<?php

use App\Models\Establishment;
use App\Models\Product;
use App\Services\Cart;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.public')] class extends Component {
    #[Locked]
    public int $establishmentId;

    public ?int $pendingProductId = null;

    public string $notice = '';
    public string $error = '';

    public function mount(Establishment $establishment): void
    {
        abort_unless($establishment->status === 'active', 404);

        $this->establishmentId = $establishment->id;
    }

    private function establishment(): Establishment
    {
        return Establishment::query()
            ->where('status', 'active')
            ->findOrFail($this->establishmentId);
    }

    private function product(int $productId): Product
    {
        return Product::query()
            ->with('category.establishment')
            ->where('status', 'active')
            ->whereHas('category', function ($query) {
                $query->where('establishment_id', $this->establishmentId)
                    ->where('status', 'active');
            })
            ->findOrFail($productId);
    }

    public function addToCart(int $productId, Cart $cart): void
    {
        $this->notice = '';
        $this->error = '';

        $product = $this->product($productId);
        $result = $cart->add($product);

        if ($result === Cart::CONFLICT) {
            $this->pendingProductId = $product->id;

            return;
        }

        if ($result === Cart::UNAVAILABLE) {
            $this->error = "{$product->name} no está disponible.";

            return;
        }

        $this->notice = "{$product->name} se agregó al carrito.";
        $this->dispatch('cart-updated');
    }

    public function replaceCart(Cart $cart): void
    {
        if ($this->pendingProductId === null) {
            return;
        }

        $productId = $this->pendingProductId;

        $cart->clear();
        $this->pendingProductId = null;

        $this->addToCart($productId, $cart);
    }

    public function keepCart(): void
    {
        $this->pendingProductId = null;
    }

    public function rendering($view): void
    {
        $view->title($this->establishment()->name);
    }

    public function with(): array
    {
        $establishment = $this->establishment();

        return [
            'establishment' => $establishment,
            'categories' => $establishment->categories()
                ->where('status', 'active')
                ->whereHas('products', fn ($query) => $query->where('status', 'active'))
                ->with(['products' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-xs font-medium uppercase tracking-wide text-indigo-700">{{ $establishment->type }}</p>
        <h1 class="mt-1 text-2xl font-bold text-zinc-900">{{ $establishment->name }}</h1>

        @if ($establishment->description)
            <p class="mt-2 text-sm text-zinc-600">{{ $establishment->description }}</p>
        @endif

        <dl class="mt-4 grid gap-2 text-sm text-zinc-600 sm:grid-cols-3">
            <div>
                <dt class="font-medium text-zinc-900">Dirección</dt>
                <dd class="break-words">{{ $establishment->address }}</dd>
            </div>
            <div>
                <dt class="font-medium text-zinc-900">Teléfono</dt>
                <dd>{{ $establishment->phone }}</dd>
            </div>
            @if ($establishment->email)
                <div>
                    <dt class="font-medium text-zinc-900">Correo</dt>
                    <dd class="break-words">{{ $establishment->email }}</dd>
                </div>
            @endif
        </dl>
    </section>

    @if ($notice)
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $notice }}
            <a href="{{ route('cart.index') }}" wire:navigate class="font-semibold underline">Ver carrito</a>
        </div>
    @endif

    @if ($error)
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    @if ($pendingProductId)
        <div role="alertdialog" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">Tu carrito contiene productos de otro establecimiento.</p>
            <p class="mt-1">¿Deseas vaciarlo y comenzar un nuevo pedido?</p>

            <div class="mt-3 flex flex-wrap gap-3">
                <button type="button" wire:click="replaceCart" class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">
                    Vaciar y agregar
                </button>
                <button type="button" wire:click="keepCart" class="rounded-lg border border-amber-300 px-4 py-2 font-semibold hover:bg-amber-100">
                    Conservar mi carrito
                </button>
            </div>
        </div>
    @endif

    @forelse ($categories as $category)
        <section wire:key="category-{{ $category->id }}">
            <h2 class="mb-3 text-lg font-semibold text-zinc-900">{{ $category->name }}</h2>

            @if ($category->description)
                <p class="-mt-2 mb-3 text-sm text-zinc-500">{{ $category->description }}</p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($category->products as $product)
                    <article
                        wire:key="product-{{ $product->id }}"
                        @class([
                            'flex flex-col overflow-hidden rounded-xl border bg-white shadow-sm',
                            'border-zinc-200' => $product->available,
                            'border-zinc-200 bg-zinc-50' => ! $product->available,
                        ])
                    >
                        <div class="flex h-36 items-center justify-center bg-zinc-100 text-3xl font-semibold text-zinc-300">
                            @if ($product->imageUrl())
                                <img
                                    src="{{ $product->imageUrl() }}"
                                    alt="{{ $product->name }}"
                                    loading="lazy"
                                    onerror="this.remove()"
                                    @class(['h-full w-full object-cover', 'opacity-50 grayscale' => ! $product->available])
                                >
                            @else
                                <span aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <h3 class="font-semibold text-zinc-900">{{ $product->name }}</h3>

                            @if ($product->description)
                                <p class="text-sm text-zinc-500">{{ $product->description }}</p>
                            @endif

                            <div class="mt-auto flex items-center justify-between gap-3 pt-2">
                                <span class="font-bold text-zinc-900">$ {{ number_format((float) $product->price, 0, ',', '.') }}</span>

                                @if ($product->available)
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="addToCart({{ $product->id }})"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        Agregar
                                    </button>
                                @else
                                    <span class="rounded-full bg-zinc-200 px-2.5 py-1 text-xs font-semibold text-zinc-700">No disponible</span>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">
            Este establecimiento todavía no tiene productos publicados.
        </div>
    @endforelse
</div>
