<?php

use App\Models\Establishment;
use App\Services\Chatbot\ChatbotService;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public int $establishmentId;

    #[Locked]
    public ?string $conversationToken = null;

    public string $message = '';

    public function mount(int $establishmentId, ChatbotService $chatbot): void
    {
        $this->establishmentId = $establishmentId;

        $establishment = $this->establishment();
        $token = session($this->key()) ?? request()->cookie($this->key());
        $conversation = $chatbot->find($establishment, is_string($token) ? $token : null);

        if ($conversation) {
            $this->conversationToken = $conversation->token;
            $chatbot->resume($establishment, $conversation);
        }
    }

    private function key(): string
    {
        return 'nyva_chat_'.$this->establishmentId;
    }

    private function establishment(): Establishment
    {
        return Establishment::query()->where('status', 'active')->findOrFail($this->establishmentId);
    }

    public function send(ChatbotService $chatbot): void
    {
        $this->validate(['message' => ['required', 'string', 'max:500']], [
            'message.required' => 'Escribe un mensaje.',
            'message.max' => 'El mensaje es demasiado largo.',
        ]);

        $text = trim($this->message);
        $this->message = '';

        $this->deliver($text, $chatbot);
    }

    public function quick(string $text, ChatbotService $chatbot): void
    {
        $this->deliver(mb_substr($text, 0, 500), $chatbot);
    }

    private function deliver(string $text, ChatbotService $chatbot): void
    {
        $establishment = $this->establishment();

        abort_unless($chatbot->isAvailable($establishment), 403);

        $limiter = 'chat:'.$this->establishmentId.':'.session()->getId().':'.request()->ip();

        if (RateLimiter::tooManyAttempts($limiter, 20)) {
            $this->addError('message', 'Enviaste muchos mensajes. Espera un momento.');

            return;
        }

        RateLimiter::hit($limiter);

        $conversation = $chatbot->find($establishment, $this->conversationToken);

        if (! $conversation) {
            $conversation = $chatbot->start($establishment);
            $this->conversationToken = $conversation->token;

            session([$this->key() => $conversation->token]);
            Cookie::queue($this->key(), $conversation->token, 60 * 24, httpOnly: true, sameSite: 'lax');
        }

        $chatbot->respond($establishment, $conversation, $text);

        $this->dispatch('chat-updated');
    }

    public function with(ChatbotService $chatbot): array
    {
        $establishment = $this->establishment();
        $conversation = $chatbot->find($establishment, $this->conversationToken);

        return [
            'available' => $chatbot->isAvailable($establishment),
            'establishment' => $establishment,
            'welcome' => $establishment->chatbot()->welcome_message
                ?: "¡Hola! Soy el asistente de {$establishment->name}. ¿En qué puedo ayudarte?",
            'messages' => $conversation?->messages()->orderBy('id')->get() ?? collect(),
        ];
    }
};
?>

<div
    x-data="{ open: false, scroll() { this.$nextTick(() => { const box = this.$refs.messages; if (box) box.scrollTop = box.scrollHeight }) } }"
    x-on:chat-updated.window="scroll()"
>
    @if ($available)
        <button
            type="button"
            x-show="! open"
            x-on:click="open = true; scroll()"
            class="fixed bottom-5 end-5 z-40 flex items-center gap-2 rounded-full bg-blue-700 px-5 py-3.5 font-semibold text-white shadow-lg transition hover:bg-blue-800"
        >
            <flux:icon name="chat-bubble-left-right" class="size-5" />
            Asistente
        </button>

        <section
            x-show="open"
            x-cloak
            aria-label="Asistente virtual"
            class="fixed inset-0 z-50 flex flex-col bg-white shadow-2xl sm:inset-auto sm:bottom-5 sm:end-5 sm:h-[36rem] sm:w-[24rem] sm:rounded-2xl sm:border sm:border-zinc-200"
        >
            <header class="flex items-center justify-between gap-3 bg-blue-800 px-4 py-3 text-white sm:rounded-t-2xl">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20 font-semibold">
                        @if ($establishment->logoUrl())
                            <img src="{{ $establishment->logoUrl() }}" alt="" class="size-full object-cover">
                        @else
                            {{ mb_substr($establishment->name, 0, 1) }}
                        @endif
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold">{{ $establishment->name }}</span>
                        <span class="block text-xs text-blue-100">Asistente virtual</span>
                    </span>
                </div>

                <button type="button" x-on:click="open = false" aria-label="Cerrar chat" class="rounded-lg p-1.5 hover:bg-white/10">
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            </header>

            <div x-ref="messages" class="flex flex-1 flex-col gap-3 overflow-y-auto bg-zinc-50 p-4 text-sm">
                <div class="max-w-[85%] self-start rounded-2xl rounded-bl-sm bg-white px-3.5 py-2.5 text-zinc-800 shadow-sm">{{ $welcome }}</div>

                @if ($messages->isEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach (['Ver menú', 'Horarios', 'Quiero hacer un pedido'] as $suggestion)
                            <button type="button" wire:click="quick(@js($suggestion))" class="rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-800 hover:bg-blue-50">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                @endif

                @foreach ($messages as $entry)
                    @php
                        $payload = $entry->payload ?? [];
                    @endphp

                    <div wire:key="message-{{ $entry->id }}" @class(['flex flex-col gap-2', 'items-end' => $entry->role === 'user', 'items-start' => $entry->role !== 'user'])>
                        <div @class([
                            'max-w-[85%] whitespace-pre-line px-3.5 py-2.5',
                            'rounded-2xl rounded-br-sm bg-blue-700 text-white' => $entry->role === 'user',
                            'rounded-2xl rounded-bl-sm bg-white text-zinc-800 shadow-sm' => $entry->role !== 'user',
                        ])>{{ $entry->content }}</div>

                        @foreach ($payload['cards'] ?? [] as $card)
                            <div class="w-full rounded-xl border border-zinc-200 bg-white p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="font-semibold text-zinc-900">{{ $card['name'] }}</span>
                                    <span class="whitespace-nowrap font-semibold text-blue-800">{{ $card['price'] }}</span>
                                </div>

                                @if ($card['description'])
                                    <p class="mt-1 text-xs text-zinc-500">{{ $card['description'] }}</p>
                                @endif

                                @if ($card['available'])
                                    <button type="button" wire:click="quick(@js('Agrega '.$card['name']))" class="mt-2 text-xs font-semibold text-blue-800 hover:underline">Agregar al pedido</button>
                                @else
                                    <span class="mt-2 inline-block rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-semibold text-zinc-700">No disponible</span>
                                @endif
                            </div>
                        @endforeach

                        @if (! empty($payload['summary']['lines']))
                            <div class="w-full rounded-xl border border-blue-200 bg-white p-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-800">Tu pedido</p>

                                @foreach ($payload['summary']['lines'] as $line)
                                    <div class="flex justify-between gap-3 py-0.5 text-zinc-700">
                                        <span>{{ $line['name'] }} × {{ $line['quantity'] }}</span>
                                        <span class="whitespace-nowrap">{{ $line['subtotal'] }}</span>
                                    </div>
                                @endforeach

                                <div class="mt-2 flex justify-between border-t border-zinc-100 pt-2 font-bold text-zinc-900">
                                    <span>Total</span>
                                    <span>{{ $payload['summary']['total'] }}</span>
                                </div>
                            </div>
                        @endif

                        @if (! empty($payload['link']))
                            <a href="{{ $payload['link'] }}" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Seguir mi pedido</a>
                        @endif

                        @if ($loop->last && ! empty($payload['suggestions']))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($payload['suggestions'] as $suggestion)
                                    <button type="button" wire:click="quick(@js($suggestion))" class="rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-800 hover:bg-blue-50">{{ $suggestion }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <div wire:loading wire:target="send, quick" class="self-start rounded-2xl bg-white px-3.5 py-2.5 text-zinc-500 shadow-sm">Escribiendo...</div>
            </div>

            <form wire:submit="send" class="border-t border-zinc-200 bg-white p-3 sm:rounded-b-2xl">
                @error('message') <p class="nyva-error mb-1">{{ $message }}</p> @enderror

                <div class="flex items-center gap-2">
                    <input type="text" wire:model="message" maxlength="500" placeholder="Escribe tu mensaje..." aria-label="Mensaje" autocomplete="off" class="nyva-input">
                    <button type="submit" wire:loading.attr="disabled" wire:target="send, quick" aria-label="Enviar" class="nyva-btn px-3">
                        <flux:icon name="paper-airplane" class="size-5" />
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
