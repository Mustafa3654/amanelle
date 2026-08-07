@extends('layouts.app')

@section('title', __('Track your order').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-20">
        <div class="text-center">
            <p class="eyebrow">{{ __('Track your order') }}</p>
            <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ __('Where is my order?') }}</h1>
            <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink-muted">
                {{ __('Enter your order number and the phone number you gave us.') }}
            </p>
        </div>

        <div class="mt-10">
            <livewire:order-lookup />
        </div>
    </section>
@endsection
