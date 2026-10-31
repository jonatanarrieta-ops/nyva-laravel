<?php

use App\Models\Establishment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] #[Title('Catálogo')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $search = trim($this->search);

        return [
            'establishments' => Establishment::query()
                ->where('status', 'active')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('type', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(12),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">Establecimientos</h1>
            <p class="mt-1 text-sm text-zinc-500">Elige un establecimiento para ver su catálogo.</p>
        </div>

        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar establecimiento..."
            aria-label="Buscar establecimiento"
            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm sm:w-72"
        >
    </div>

    @if ($establishments->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center text-sm text-zinc-500">
            No se encontraron establecimientos.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($establishments as $establishment)
                <a
                    href="{{ route('catalog.show', $establishment) }}"
                    wire:navigate
                    wire:key="establishment-{{ $establishment->id }}"
                    class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-indigo-300"
                >
                    <span class="text-xs font-medium uppercase tracking-wide text-indigo-700">{{ $establishment->type }}</span>
                    <span class="text-lg font-semibold text-zinc-900">{{ $establishment->name }}</span>

                    @if ($establishment->description)
                        <span class="line-clamp-2 text-sm text-zinc-500">{{ $establishment->description }}</span>
                    @endif

                    <span class="mt-auto pt-2 text-sm text-zinc-600">{{ $establishment->address }}</span>
                </a>
            @endforeach
        </div>

        {{ $establishments->links() }}
    @endif
</div>
