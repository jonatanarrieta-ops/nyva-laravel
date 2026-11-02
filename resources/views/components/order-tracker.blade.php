@props(['order'])

@php
    $steps = [
        'pending' => 'Pedido recibido',
        'confirmed' => 'Confirmado',
        'preparing' => 'Preparando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
    ];

    $currentStep = array_search($order->status, array_keys($steps), true);
@endphp

<section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-semibold text-zinc-900">Seguimiento</h2>

    @if ($order->status === 'cancelled')
        <p class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            Pedido cancelado
        </p>
    @else
        <ol class="mt-4 flex flex-col gap-3 sm:flex-row sm:gap-2">
            @foreach ($steps as $status => $label)
                @php
                    $reached = $currentStep !== false && $loop->index <= $currentStep;
                @endphp

                <li
                    data-step="{{ $status }}"
                    data-reached="{{ $reached ? 'true' : 'false' }}"
                    @if ($loop->index === $currentStep) aria-current="step" @endif
                    class="flex items-center gap-3 sm:flex-1 sm:flex-col sm:text-center"
                >
                    <span @class([
                        'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-indigo-600 text-white' => $reached,
                        'bg-zinc-100 text-zinc-500' => ! $reached,
                    ])>
                        {{ $loop->iteration }}
                    </span>

                    <span @class([
                        'text-sm',
                        'font-semibold text-zinc-900' => $reached,
                        'text-zinc-500' => ! $reached,
                    ])>
                        {{ $label }}
                    </span>
                </li>
            @endforeach
        </ol>
    @endif
</section>
