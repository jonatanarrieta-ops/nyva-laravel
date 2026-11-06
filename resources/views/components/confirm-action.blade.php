@props(['name', 'action', 'title', 'message', 'confirm' => 'Eliminar', 'method' => 'DELETE'])

<div {{ $attributes->only('class') }}>
    <flux:modal.trigger :name="$name">
        {{ $slot }}
    </flux:modal.trigger>

    <flux:modal :name="$name" class="max-w-md">
        <form method="POST" action="{{ $action }}" class="space-y-6">
            @csrf
            @method($method)

            <div>
                <flux:heading size="lg">{{ $title }}</flux:heading>
                <flux:subheading>{{ $message }}</flux:subheading>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancelar</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" type="submit">{{ $confirm }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
