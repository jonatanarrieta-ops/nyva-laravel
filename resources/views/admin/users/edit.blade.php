@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Editar usuario" :subtitle="$user->email" />

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="nyva-card grid gap-5 p-5 md:grid-cols-2">
            @method('PUT')
            @include('admin.users._form')
        </form>
    </div>
@endsection
