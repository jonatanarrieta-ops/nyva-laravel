@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Nuevo plan" subtitle="Define precios, límites y funcionalidades." />

        <form method="POST" action="{{ route('admin.plans.store') }}" class="nyva-card flex flex-col gap-5 p-5">
            @include('admin.plans._form', ['plan' => null])
        </form>
    </div>
@endsection
