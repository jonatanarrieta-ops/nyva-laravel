@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-14 text-center') }}>
    <span class="flex size-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
        <flux:icon :name="$icon" class="size-6" />
    </span>

    <h3 class="mt-4 font-semibold text-slate-800">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
