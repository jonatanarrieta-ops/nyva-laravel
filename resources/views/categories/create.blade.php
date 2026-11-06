@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Nueva categoría" subtitle="Completa los datos de la categoría." />

        <section class="nyva-card p-5 md:p-6">
            @if ($establishments->isEmpty())
                <x-ui.alert tone="warning">Primero debes registrar un establecimiento para poder crear categorías.</x-ui.alert>
            @else
                <form method="POST" action="{{ route('categories.store') }}" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('categories._form', ['category' => null])
                </form>
            @endif
        </section>
    </div>
@endsection
