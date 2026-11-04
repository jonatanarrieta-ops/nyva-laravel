<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/*
 * Presentación de la plataforma. No muestra establecimientos: cada negocio
 * comparte con sus clientes el enlace de su propia página.
 */
new #[Layout('layouts.public')] #[Title('Asistente virtual para negocios')] class extends Component {
    //
};
?>

<div class="flex flex-col gap-8">
    <section class="rounded-2xl bg-gradient-to-r from-blue-800 to-blue-500 p-8 text-white shadow-sm sm:p-12">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-100">NYVA</p>
        <h1 class="mt-2 max-w-2xl text-3xl font-bold sm:text-4xl">Asistente virtual para la atención al cliente y la gestión de pedidos</h1>
        <p class="mt-4 max-w-2xl text-blue-100">
            Cada negocio tiene su propia página con su catálogo, sus horarios y un asistente que atiende y recibe pedidos.
        </p>

        <a href="{{ route('login') }}" class="mt-6 inline-flex rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-50">
            Acceso para negocios
        </a>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['Página propia', 'Cada establecimiento comparte el enlace de su página con sus clientes.'],
            ['Asistente virtual', 'Responde preguntas con la información real del negocio y toma pedidos.'],
            ['Pedidos en línea', 'El cliente pide sin crear una cuenta y sigue su pedido con un enlace seguro.'],
        ] as [$title, $text])
            <article class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-zinc-900">{{ $title }}</h2>
                <p class="mt-2 text-sm text-zinc-600">{{ $text }}</p>
            </article>
        @endforeach
    </section>

    <p class="text-center text-sm text-zinc-500">
        ¿Buscas un negocio? Pídele el enlace de su página.
    </p>
</div>
