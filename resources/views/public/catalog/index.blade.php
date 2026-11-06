@extends('layouts.public')

@section('content')
    <div class="flex flex-col gap-20 pb-6 sm:gap-24">
        <section class="grid items-center gap-12 pt-4 lg:grid-cols-[1.1fr_0.9fr] lg:pt-10">
            <div class="max-w-xl">
                <h1 class="text-4xl font-bold leading-[1.1] tracking-tight text-slate-900 sm:text-5xl">
                    Tu negocio responde y toma pedidos aunque estés en la cocina
                </h1>

                <p class="mt-6 text-lg leading-relaxed text-slate-600">
                    NYVA es un asistente virtual para restaurantes, hoteles y cocinas ocultas de Sincelejo.
                    Contesta preguntas sobre tu menú, precios y horarios las 24 horas, y recibe pedidos solo cuando estás abierto.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('login') }}" class="rounded-lg bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                        Acceso para negocios
                    </a>
                    <a href="#como-funciona" class="rounded-lg px-5 py-3 text-sm font-semibold text-blue-800 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700">
                        Ver cómo funciona
                    </a>
                </div>

                <p class="mt-6 text-sm text-slate-500">
                    Tus clientes no descargan nada: entran desde el enlace de tu negocio.
                </p>
            </div>

            <figure class="relative mx-auto w-full max-w-md">
                <div aria-hidden="true" class="absolute -inset-4 -z-10 rounded-[2rem] bg-blue-100/70 sm:-inset-6"></div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-blue-900/10">
                    <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <span class="flex size-10 items-center justify-center rounded-full bg-blue-700 font-bold text-white">T</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-900">Tu negocio</p>
                            <p class="flex items-center gap-1.5 text-xs text-slate-500">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                Asistente en línea
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 bg-white px-5 py-6 text-sm">
                        <p class="text-center text-xs text-slate-400">Hoy, 10:47 p. m.</p>

                        <div class="nyva-bubble ml-auto max-w-[80%] rounded-2xl rounded-br-md bg-slate-100 px-4 py-2.5 text-slate-800" style="animation-delay: .2s">
                            Hola, ¿todavía reciben pedidos?
                        </div>

                        <div class="nyva-bubble max-w-[85%] rounded-2xl rounded-bl-md bg-blue-700 px-4 py-2.5 text-white" style="animation-delay: .9s">
                            ¡Hola! Sí, atendemos hasta las 11:30 p. m. ¿Qué te gustaría pedir?
                        </div>

                        <div class="nyva-bubble ml-auto max-w-[80%] rounded-2xl rounded-br-md bg-slate-100 px-4 py-2.5 text-slate-800" style="animation-delay: 1.6s">
                            Una hamburguesa clásica y una gaseosa
                        </div>

                        <div class="nyva-bubble max-w-[90%] rounded-2xl rounded-bl-md bg-blue-700 px-4 py-3 text-white" style="animation-delay: 2.3s">
                            <p>Revisa tu pedido:</p>
                            <dl class="mt-2 flex flex-col gap-1 rounded-xl bg-white/10 px-3 py-2.5 text-blue-50">
                                <div class="flex justify-between gap-6"><dt>Hamburguesa clásica × 1</dt><dd>$ 25.000</dd></div>
                                <div class="flex justify-between gap-6"><dt>Gaseosa × 1</dt><dd>$ 5.000</dd></div>
                                <div class="mt-1 flex justify-between gap-6 border-t border-white/20 pt-1.5 font-semibold text-white"><dt>Total</dt><dd>$ 30.000</dd></div>
                            </dl>
                            <p class="mt-2">¿Confirmas el pedido?</p>
                        </div>

                        <div class="nyva-bubble flex gap-2" style="animation-delay: 2.9s" aria-hidden="true">
                            <span class="rounded-full border border-blue-200 px-3 py-1 text-xs font-semibold text-blue-800">Confirmar</span>
                            <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">Cambiar algo</span>
                        </div>
                    </div>
                </div>

                <figcaption class="sr-only">Ejemplo de una conversación en la que el asistente responde el horario y toma un pedido.</figcaption>
            </figure>
        </section>

        <section aria-labelledby="problema">
            <h2 id="problema" class="max-w-2xl text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Lo que pasa en hora pico</h2>

            <div class="mt-8 grid gap-8 md:grid-cols-3">
                @foreach ([
                    ['Las mismas preguntas, todo el día', '¿Cuánto cuesta? ¿A qué hora cierran? ¿Hacen domicilios? Responderlo a mano le quita tiempo a la cocina.'],
                    ['Clientes esperando respuesta', 'Mientras los mensajes se acumulan, el cliente se cansa de esperar y le pide a otro negocio.'],
                    ['Pedidos anotados a mano', 'Entre chats y llamadas se pierden direcciones, cantidades y totales.'],
                ] as [$title, $text])
                    <div class="border-l-2 border-blue-200 pl-5">
                        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section id="como-funciona" aria-labelledby="como-funciona-titulo" class="scroll-mt-8 rounded-3xl bg-white p-8 shadow-sm ring-1 ring-slate-200 sm:p-12">
            <h2 id="como-funciona-titulo" class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Cómo funciona</h2>

            <ol class="mt-10 grid gap-10 md:grid-cols-3 md:gap-8">
                @foreach ([
                    ['Configura tu negocio', 'Desde el panel cargas tu menú, precios, horarios y preguntas frecuentes.'],
                    ['Comparte tu enlace', 'Publícalo en tus redes o en tu perfil de WhatsApp. Cada negocio tiene su propia página.'],
                    ['El asistente atiende', 'Responde con tus datos reales y registra los pedidos para que los veas en el panel.'],
                ] as $index => [$title, $text])
                    <li class="relative">
                        <span class="flex size-11 items-center justify-center rounded-full bg-blue-700 text-lg font-bold text-white">{{ $index + 1 }}</span>
                        @if (! $loop->last)
                            <span aria-hidden="true" class="absolute left-14 right-0 top-5.5 hidden h-px bg-blue-200 md:block"></span>
                        @endif
                        <h3 class="mt-5 font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section aria-labelledby="responde" class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <h2 id="responde" class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Qué responde el asistente</h2>
                <p class="mt-4 max-w-md leading-relaxed text-slate-600">
                    Usa solo la información que tú configuras. Si no la tiene, se lo dice al cliente en lugar de inventar.
                </p>
            </div>

            <ul class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                @foreach ([
                    'Menú, precios y disponibilidad de cada producto',
                    'Horarios de atención y si estás abierto ahora',
                    'Dirección, teléfono y correo del negocio',
                    'Las preguntas frecuentes que tú escribes',
                    'Pedidos con resumen y confirmación del cliente',
                    'Un enlace para seguir el estado del pedido',
                ] as $item)
                    <li class="flex gap-3">
                        <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-blue-700" />
                        <span class="text-slate-700">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        @if ($plans->isNotEmpty())
            <section aria-labelledby="planes">
                <h2 id="planes" class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Planes</h2>
                <p class="mt-3 max-w-xl leading-relaxed text-slate-600">
                    Pagas una suscripción mensual o anual. La plataforma, el alojamiento y las actualizaciones corren por nuestra cuenta.
                </p>

                <div class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>

                            <p class="mt-3">
                                <span class="text-3xl font-bold tracking-tight text-slate-900">$ {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}</span>
                                <span class="text-sm text-slate-500">al mes</span>
                            </p>

                            @if ($plan->annual_price)
                                <p class="mt-1 text-sm text-slate-500">o $ {{ number_format((float) $plan->annual_price, 0, ',', '.') }} al año</p>
                            @endif

                            @if ($plan->description)
                                <p class="mt-4 text-sm leading-relaxed text-slate-600">{{ $plan->description }}</p>
                            @endif

                            <ul class="mt-5 flex flex-col gap-2.5 border-t border-slate-100 pt-5 text-sm text-slate-700">
                                <li class="flex gap-2.5">
                                    <flux:icon name="check" class="size-4 shrink-0 translate-y-0.5 text-blue-700" />
                                    {{ $plan->product_limit ? "Hasta {$plan->product_limit} productos" : 'Productos ilimitados' }}
                                </li>
                                @foreach ($plan->features ?? [] as $feature)
                                    <li class="flex gap-2.5">
                                        <flux:icon name="check" class="size-4 shrink-0 translate-y-0.5 text-blue-700" />
                                        {{ \App\Models\Plan::FEATURES[$feature] ?? $feature }}
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="rounded-3xl bg-blue-700 px-8 py-12 text-white sm:px-12">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">¿Tienes un negocio en Sincelejo?</h2>
                    <p class="mt-3 leading-relaxed text-blue-100">
                        Creamos tu cuenta y dejamos tu asistente listo para compartir con tus clientes.
                    </p>
                </div>
                <a href="{{ route('login') }}" class="shrink-0 self-start rounded-lg bg-white px-5 py-3 text-sm font-semibold text-blue-800 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white md:self-auto">
                    Acceso para negocios
                </a>
            </div>
        </section>

        <footer class="border-t border-slate-200 pt-6 text-sm text-slate-500">
            NYVA es un proyecto académico de la Corporación Universitaria Antonio José de Sucre, Sincelejo.
        </footer>
    </div>
@endsection
