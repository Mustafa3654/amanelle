@extends('layouts.app')

@section('title', __('Cart').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
        <h1 class="font-display text-2xl">{{ __('Cart') }}</h1>

        <p class="mt-6 text-sm text-ink-muted">{{ __('Your cart is empty') }}</p>

        <a href="{{ route('home') }}"
           class="mt-6 inline-block rounded-full bg-accent-fill px-6 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#0d0b09]">
            {{ __('Start shopping') }}
        </a>
    </section>
@endsection
