<?php

use App\Concerns\WithEstablishmentContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Preguntas frecuentes')] class extends Component {
    use WithEstablishmentContext;

    public bool $showForm = false;
    public ?int $faqId = null;
    public string $question = '';
    public string $answer = '';
    public bool $active = true;

    protected function establishmentPermission(): string
    {
        return 'settings';
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->reset('faqId', 'question', 'answer', 'active');
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $faq = $this->currentEstablishment()->frequentlyAskedQuestions()->findOrFail($id);

        $this->resetValidation();
        $this->faqId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->active = $faq->status === 'active';
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $establishment = $this->currentEstablishment();

        Gate::authorize('update', $establishment);

        $data = $this->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
        ], [
            'question.required' => 'La pregunta es obligatoria.',
            'answer.required' => 'La respuesta es obligatoria.',
        ]);

        $data['status'] = $this->active ? 'active' : 'inactive';

        if ($this->faqId !== null) {
            $establishment->frequentlyAskedQuestions()->findOrFail($this->faqId)->update($data);
        } else {
            $establishment->frequentlyAskedQuestions()->create($data);
        }

        session()->flash('notice', 'Pregunta guardada.');

        $this->showForm = false;
    }

    public function delete(int $id): void
    {
        $this->currentEstablishment()->frequentlyAskedQuestions()->findOrFail($id)->delete();

        session()->flash('notice', 'Pregunta eliminada.');
    }

    public function with(): array
    {
        return [
            'establishments' => $this->establishmentOptions(),
            'faqs' => $this->currentEstablishment()->frequentlyAskedQuestions()->orderBy('question')->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6 p-4 md:p-6">
    <x-page-header title="Preguntas frecuentes" subtitle="El chatbot usa estas respuestas para atender a tus clientes.">
        <x-establishment-picker :establishments="$establishments" />
        <button type="button" wire:click="create" class="nyva-btn">Nueva pregunta</button>
    </x-page-header>

    <x-alerts />

    @if ($showForm)
        <form wire:submit="save" class="nyva-card flex flex-col gap-5 p-5">
            <div>
                <label for="question" class="nyva-label">Pregunta</label>
                <input id="question" type="text" wire:model="question" placeholder="Ej. ¿Qué métodos de pago aceptan?" class="nyva-input">
                @error('question') <p class="nyva-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="answer" class="nyva-label">Respuesta</label>
                <textarea id="answer" wire:model="answer" rows="3" class="nyva-input"></textarea>
                @error('answer') <p class="nyva-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="active"> Activa</label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" wire:click="cancel" class="nyva-btn-secondary">Cancelar</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="nyva-btn">Guardar</button>
            </div>
        </form>
    @endif

    <div class="flex flex-col gap-3">
        @forelse ($faqs as $faq)
            <article wire:key="faq-{{ $faq->id }}" class="nyva-card p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="font-medium">{{ $faq->question }}</h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ $faq->answer }}</p>

                        @if ($faq->status !== 'active')
                            <span class="mt-2 inline-block rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium dark:bg-zinc-800">Inactiva</span>
                        @endif
                    </div>

                    <div class="flex shrink-0 gap-3 text-sm">
                        <button type="button" wire:click="edit({{ $faq->id }})" class="nyva-link">Editar</button>
                        <button type="button" wire:click="delete({{ $faq->id }})" wire:confirm="¿Eliminar esta pregunta?" class="nyva-link-danger">Eliminar</button>
                    </div>
                </div>
            </article>
        @empty
            <div class="nyva-card p-10 text-center text-sm text-zinc-500">Todavía no tienes preguntas frecuentes.</div>
        @endforelse
    </div>
</div>
