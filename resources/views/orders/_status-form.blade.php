@php($transitions = \App\Models\Order::TRANSITIONS[$order->status] ?? [])

@if (count($transitions) > 0)
    <form method="POST" action="{{ route('orders.update', $order->id) }}" class="flex items-center gap-2">
        @csrf
        @method('PATCH')
        <select name="status" required aria-label="Cambiar estado del pedido {{ $order->id }}" class="nyva-input w-auto! py-1.5! text-xs">
            <option value="">Cambiar estado...</option>
            @foreach ($transitions as $value)
                <option value="{{ $value }}">{{ \App\Models\Order::STATUS_LABELS[$value] }}</option>
            @endforeach
        </select>
        <button type="submit" class="nyva-btn-secondary px-3! py-1.5! text-xs">Aplicar</button>
    </form>
@endif
