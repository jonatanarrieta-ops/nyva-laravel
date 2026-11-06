@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Nuevo usuario" subtitle="Crea una cuenta y asígnale un rol." />

        <form method="POST" action="{{ route('admin.users.store') }}" class="nyva-card grid gap-5 p-5 md:grid-cols-2">
            @include('admin.users._form', ['user' => null])
        </form>
    </div>
@endsection
