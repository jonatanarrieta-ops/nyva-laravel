@props(['title', 'subtitle' => null])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>

        @if ($subtitle)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($slot->isNotEmpty())
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
