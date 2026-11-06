@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6 lg:p-8">
        <x-page-header title="Editar categoría" :subtitle="$category->name" />

        <section class="nyva-card p-5 md:p-6">
            <form method="POST" action="{{ route('categories.update', $category->id) }}" class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @method('PUT')
                @include('categories._form')
            </form>
        </section>
    </div>
@endsection
