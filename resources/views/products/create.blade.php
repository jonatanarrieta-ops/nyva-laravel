@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Nuevo producto" subtitle="Completa la información que verán tus clientes." />

        <section class="nyva-card p-5 md:p-6">
            @if ($categories->isEmpty())
                <x-ui.alert tone="warning">Primero debes crear un establecimiento y al menos una categoría.</x-ui.alert>
            @else
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    @include('products._form', ['product' => null])
                </form>
            @endif
        </section>
    </div>
@endsection
