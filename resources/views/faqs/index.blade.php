@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Preguntas frecuentes" subtitle="El chatbot usa estas respuestas para atender a tus clientes.">
            <x-establishment-switcher :establishments="$establishments" :current="$establishment" :action="route('faqs.index')" />
            <a href="{{ route('faqs.create', ['establishment' => $establishment->id]) }}" class="nyva-btn">Nueva pregunta</a>
        </x-page-header>

        @if (session('success'))
            <x-ui.alert>{{ session('success') }}</x-ui.alert>
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($faqs as $faq)
                <article class="nyva-card p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h2 class="font-medium">{{ $faq->question }}</h2>
                            <p class="mt-1 text-sm text-zinc-500">{{ $faq->answer }}</p>

                            @if ($faq->status !== 'active')
                                <span class="mt-2 inline-block rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium dark:bg-zinc-800">Inactiva</span>
                            @endif
                        </div>

                        <div class="flex shrink-0 gap-3 text-sm">
                            <a href="{{ route('faqs.edit', $faq->id) }}" class="nyva-link">Editar</a>
                            <form method="POST" action="{{ route('faqs.destroy', $faq->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('¿Eliminar esta pregunta?')" class="nyva-link-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="nyva-card p-10 text-center text-sm text-zinc-500">Todavía no tienes preguntas frecuentes.</div>
            @endforelse
        </div>
    </div>
@endsection
