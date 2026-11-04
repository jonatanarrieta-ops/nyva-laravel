<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Solo se aceptan rutas internas.
        if (is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $this->redirect($url, navigate: true);
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function with(): array
    {
        return [
            'unread' => Auth::user()->unreadNotifications()->count(),
            'items' => $this->open ? Auth::user()->notifications()->latest()->limit(10)->get() : collect(),
        ];
    }
};
?>

<div wire:poll.30s class="relative">
    <button type="button" wire:click="toggle" aria-label="Notificaciones" class="relative flex size-10 items-center justify-center rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
        <flux:icon name="bell" class="size-5" />

        @if ($unread > 0)
            <span class="absolute -end-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-semibold text-white">{{ $unread }}</span>
        @endif
    </button>

    @if ($open)
        <div class="absolute end-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <span class="text-sm font-semibold">Notificaciones</span>

                @if ($unread > 0)
                    <button type="button" wire:click="markAllAsRead" class="text-xs font-medium text-blue-800 hover:underline dark:text-blue-300">Marcar todas</button>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto">
                @forelse ($items as $item)
                    <button
                        type="button"
                        wire:key="notification-{{ $item->id }}"
                        wire:click="markAsRead('{{ $item->id }}')"
                        @class([
                            'block w-full border-b border-zinc-100 px-4 py-3 text-start text-sm last:border-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800',
                            'bg-blue-50/60 dark:bg-blue-950/40' => $item->read_at === null,
                        ])
                    >
                        <span class="block font-medium">{{ $item->data['title'] ?? '' }}</span>
                        <span class="block text-zinc-500">{{ $item->data['message'] ?? '' }}</span>
                        <span class="mt-1 block text-xs text-zinc-400">{{ $item->created_at?->format('d/m/Y H:i') }}</span>
                    </button>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-zinc-500">No tienes notificaciones.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
