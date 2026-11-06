@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header :title="'Conversación #'.$conversation->id" :subtitle="$conversation->establishment?->name ?? '—'">
            <x-ui.button variant="secondary" :href="route('conversations.index', ['establishment' => $conversation->establishment_id])">Volver a conversaciones</x-ui.button>
        </x-page-header>

        <section class="nyva-card grid grid-cols-2 gap-4 p-5 md:grid-cols-4">
            <div>
                <p class="text-xs text-slate-500">Estado</p>
                <x-ui.badge class="mt-1" :tone="$conversation->status === 'active' ? 'green' : 'slate'">{{ $conversation->statusLabel() }}</x-ui.badge>
            </div>
            <div>
                <p class="text-xs text-slate-500">Inicio</p>
                <p class="mt-1 text-sm font-medium text-slate-800">{{ $conversation->started_at?->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Fin</p>
                <p class="mt-1 text-sm font-medium text-slate-800">{{ $conversation->ended_at?->format('d/m/Y H:i') ?? 'En curso' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-500">Mensajes</p>
                <p class="mt-1 text-sm font-medium text-slate-800">{{ $conversation->messages->count() }}</p>
            </div>
        </section>

        <section class="nyva-card flex flex-col gap-3 p-5">
            @forelse ($conversation->messages as $message)
                @php($fromCustomer = $message->role === 'user')

                <div @class(['flex flex-col gap-1', 'items-end' => ! $fromCustomer, 'items-start' => $fromCustomer])>
                    <p class="text-xs text-slate-500">
                        {{ $fromCustomer ? 'Cliente' : 'Asistente' }} · {{ $message->created_at?->format('d/m/Y H:i') }}
                        @if ($message->payload[\App\Services\Chatbot\ChatbotService::ORDER_FLAG] ?? false)
                            · <span class="font-medium text-blue-700">Pedido</span>
                        @endif
                    </p>

                    <div @class([
                        'max-w-xl whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm',
                        'bg-slate-100 text-slate-800' => $fromCustomer,
                        'bg-blue-50 text-slate-800 ring-1 ring-inset ring-blue-100' => ! $fromCustomer,
                    ])>{{ $message->content }}</div>

                    @if (! empty($message->payload['summary']['lines']))
                        <p class="text-xs text-slate-500">Total del pedido: {{ $message->payload['summary']['total'] }}</p>
                    @endif
                </div>
            @empty
                <x-ui.empty-state icon="chat-bubble-left-right" title="Esta conversación no tiene mensajes" />
            @endforelse
        </section>
    </div>
@endsection
