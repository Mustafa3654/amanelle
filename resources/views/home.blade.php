@extends('layouts.app')

@section('title', config('app.name').' — '.__('Authentic Gulf fragrance'))

@section('content')

    {{-- The hero follows the theme. Pinning it to near-black made the light
         page read as two unrelated documents stacked on each other. --}}
    <section class="relative isolate overflow-hidden bg-surface text-ink">

        <x-atmosphere />

        <div class="relative z-10 mx-auto flex max-w-3xl flex-col items-center px-5 py-14 text-center sm:px-6 sm:py-32">

            <div class="logo-ring fade-up fade-up-2 grid size-16 place-items-center sm:size-24">
                <img src="{{ asset('images/logo.jpeg') }}"
                     alt=""
                     width="80"
                     height="80"
                     class="size-13 object-cover sm:size-20">
            </div>

            <h1 class="wordmark-hero fade-up fade-up-3 mt-7 font-light text-accent sm:mt-8">
                AMANELLE
            </h1>

            <p class="fade-up fade-up-4 mt-3 font-display text-base font-light italic text-ink-muted sm:text-lg">
                {{ __('Where every scent tells a story') }}
            </p>

            {{-- The divider from the coming-soon page: two hairlines meeting a
                 rotated square. Kept because it is load-bearing brand
                 furniture, not decoration I added. --}}
            <div class="fade-up fade-up-5 mt-8 flex w-full max-w-sm items-center gap-4" aria-hidden="true">
                <span class="h-px flex-1 bg-gradient-to-r from-transparent to-accent-fill/40"></span>
                <span class="size-1.5 rotate-45 bg-accent-fill"></span>
                <span class="h-px flex-1 bg-gradient-to-l from-transparent to-accent-fill/40"></span>
            </div>

            <p class="fade-up fade-up-6 mt-7 max-w-md text-sm leading-relaxed text-ink-muted sm:mt-8">
                {{ __('Original Saudi and Gulf perfumes, and authentic cosmetics — at a price that makes sense.') }}
            </p>

            {{-- Full-width stacked buttons on a phone: two pills side by side
                 at this tracking wrap into a ragged two-line mess, and a 44px
                 tap target matters more here than the horizontal pairing. --}}
            <div class="fade-up fade-up-7 mt-9 flex w-full max-w-xs flex-col items-stretch gap-3
                        sm:mt-10 sm:w-auto sm:max-w-none sm:flex-row sm:items-center sm:justify-center">
                <a href="{{ route('category', 'perfumes') }}"
                   class="rounded-full bg-accent-fill px-7 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90">
                    {{ __('Shop perfumes') }}
                </a>
                <a href="{{ route('category', 'skincare') }}"
                   class="rounded-full border border-accent/40 px-7 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-accent transition hover:bg-accent-fill/10">
                    {{ __('Shop skincare') }}
                </a>
            </div>
        </div>
    </section>

    {{-- Products come first below the hero. The category tiles that used to sit
         here only repeated what the shop page already does, and pushed the
         actual merchandise a full screen further down. --}}
    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-20">
            <div class="flex items-baseline justify-between">
                <p class="eyebrow">{{ __('Featured') }}</p>
                <a href="{{ route('shop') }}" class="text-xs text-accent hover:underline">
                    {{ __('View all') }}
                </a>
            </div>

            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($featured as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        </section>
    @endif

    <x-instagram-feed />

    {{-- The authenticity promise. It is the account's single biggest content
         theme, so it earns a band of its own rather than a line in the footer. --}}
    <section class="border-y border-hairline bg-surface-2">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:grid-cols-3 sm:px-6">
            @foreach ([
                ['100% authentic', 'Sourced direct from the houses themselves, never a grey market.'],
                ['Priced honestly', 'The same quality as the designer bottle, without the designer markup.'],
                ['Delivered in Lebanon', 'Brought in from Saudi Arabia and the Gulf, delivered across Lebanon.'],
            ] as [$title, $body])
                <div>
                    <p class="font-display text-lg text-accent">{{ __($title) }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">{{ __($body) }}</p>
                </div>
            @endforeach
        </div>
    </section>

@endsection
