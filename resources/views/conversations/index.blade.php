@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Conversaciones" subtitle="Historial de lo que tus clientes hablan con el asistente virtual.">
            <x-establishment-switcher :establishments="$establishments" :current="$establishment" :action="route('conversations.index')" />
        </x-page-header>

        <form method="GET" action="{{ route('conversations.index') }}" class="nyva-card grid grid-cols-1 items-end gap-3 p-4 sm:grid-cols-[16rem_auto]">
            <input type="hidden" name="establishment" value="{{ $establishment->id }}">

            <x-ui.select label="Estado" name="status">
                <option value="">Todas</option>
                @foreach (\App\Models\Conversation::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="secondary" class="justify-self-start">Filtrar</x-ui.button>
        </form>

        <section class="nyva-card overflow-hidden">
            @if ($conversations->isEmpty())
                <x-ui.empty-state
                    icon="chat-bubble-left-right"
                    title="No hay conversaciones para mostrar"
                    description="Aparecen aquí cuando un cliente escribe al asistente desde tu página pública."
                />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($conversations as $conversation)
                        <article class="grid grid-cols-1 gap-3 p-4 md:grid-cols-[minmax(0,1.4fr)_8rem_6rem_auto] md:items-center md:gap-4 md:px-5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800">Conversación #{{ $conversation->id }}</p>
                                <p class="text-xs text-slate-500">
                                    Inicio: {{ $conversation->started_at?->format('d/m/Y H:i') ?? '—' }}
                                    · Última actividad: {{ $conversation->last_activity_at?->format('d/m/Y H:i') ?? '—' }}
                                </p>
                            </div>

                            <p class="text-sm text-slate-600">{{ $conversation->messages_count }} {{ $conversation->messages_count === 1 ? 'mensaje' : 'mensajes' }}</p>

                            <div>
                                <x-ui.badge :tone="$conversation->status === 'active' ? 'green' : 'slate'">{{ $conversation->statusLabel() }}</x-ui.badge>
                            </div>

                            <a href="{{ route('conversations.show', $conversation->id) }}" class="nyva-link justify-self-start text-sm md:justify-self-end">Ver conversación</a>
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($conversations->hasPages())
                <div class="border-t border-slate-100 px-4 py-3">{{ $conversations->links() }}</div>
            @endif
        </section>
    </div>
@endsection
