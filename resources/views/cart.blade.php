@extends('layouts.app')

@section('title', __('Cart').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-16">
        <h1 class="font-display text-2xl sm:text-3xl">{{ __('Cart') }}</h1>

        <div class="mt-8">
            <livewire:cart-panel />
        </div>
    </section>
@endsection
