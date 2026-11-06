@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Nueva suscripción" subtitle="Asigna un plan a un establecimiento." />

        <form method="POST" action="{{ route('admin.subscriptions.store') }}" class="nyva-card grid gap-5 p-5 md:grid-cols-2">
            @include('admin.subscriptions._form', ['subscription' => null])
        </form>
    </div>
@endsection
