@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Nuevo establecimiento" subtitle="Registra un negocio y su propietario." />

        <form method="POST" action="{{ route('admin.establishments.store') }}" class="nyva-card flex flex-col gap-5 p-5">
            @include('admin.establishments._form', ['establishment' => null])
        </form>
    </div>
@endsection
