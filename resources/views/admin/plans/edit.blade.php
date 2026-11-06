@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <x-page-header title="Editar plan" :subtitle="$plan->name" />

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="nyva-card flex flex-col gap-5 p-5">
            @method('PUT')
            @include('admin.plans._form')
        </form>
    </div>
@endsection
