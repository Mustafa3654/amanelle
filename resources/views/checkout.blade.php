@extends('layouts.app')

@section('title', __('Checkout').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 sm:py-16">
        <p class="eyebrow">{{ __('Checkout') }}</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ __('Where are we sending it?') }}</h1>

        <div class="mt-8">
            <livewire:checkout-form />
        </div>
    </section>
@endsection
