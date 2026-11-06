@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Editar pregunta" :subtitle="$establishment->name" />

        <form method="POST" action="{{ route('faqs.update', $faq->id) }}" class="nyva-card flex flex-col gap-5 p-5">
            @method('PUT')
            @include('faqs._form')
        </form>
    </div>
@endsection
