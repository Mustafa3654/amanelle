@extends('layouts.app')

@section('title', __('About us').' — '.config('app.name'))

@section('content')

    <section class="relative isolate overflow-hidden bg-surface text-ink">
        <x-atmosphere />

        <div class="relative z-10 mx-auto max-w-3xl px-5 py-16 text-center sm:px-6 sm:py-28">
            <p class="eyebrow fade-up fade-up-1">{{ __('About us') }}</p>
            <h1 class="wordmark-hero fade-up fade-up-2 mt-4 font-light text-accent">AMANELLE</h1>
            <p class="fade-up fade-up-3 mt-4 font-display text-base italic text-ink-muted sm:text-lg">
                {{ __('Where every scent tells a story') }}
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-5 py-14 sm:px-6 sm:py-20">

        <p class="font-display text-xl leading-relaxed sm:text-2xl">
            {{ __('We started Amanelle because too many people were paying designer prices for a bottle — or worse, paying real money for a fake.') }}
        </p>

        <div class="mt-8 space-y-5 text-sm leading-relaxed text-ink-muted">
            <p>{{ __('Amanelle sources original perfumes and cosmetics directly from the Gulf houses that make them — ASSAF, Gissah, Gulf Orchid, Match, Maison Alhambra — alongside the Korean skincare our customers keep asking us about. Every product is the real thing, bought through proper channels, and we can tell you exactly where it came from.') }}</p>

            <p>{{ __('The Gulf has been making world-class fragrance for a long time. Much of it stands beside the designer bottles you already know, and costs a fraction of the price. That is not a compromise — it is simply what happens when you take out the marketing budget.') }}</p>

            <p>{{ __('We bring it all into Lebanon, where finding the genuine article has become genuinely difficult — and we deliver across the country.') }}</p>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-3">
            @foreach ([
                ['Authenticity first', 'Sourced from the houses themselves. If we cannot verify it, we do not sell it.'],
                ['A fair price', 'The quality of the designer bottle, without the designer markup.'],
                ['Across Lebanon', 'Delivered nationwide, priced in dollars or pounds.'],
            ] as [$title, $body])
                <div class="rounded-lg border border-hairline bg-surface-2 p-5">
                    <p class="font-display text-base text-accent">{{ __($title) }}</p>
                    <p class="mt-2 text-xs leading-relaxed text-ink-muted">{{ __($body) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex flex-col items-start gap-4 border-t border-hairline pt-10 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink-muted">{{ __('Questions about a product? We answer every message.') }}</p>
            <a href="{{ route('contact') }}"
               class="rounded-full bg-accent-fill px-7 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90">
                {{ __('Contact us') }}
            </a>
        </div>
    </section>

@endsection
