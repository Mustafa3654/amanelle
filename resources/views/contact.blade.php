@extends('layouts.app')

@section('title', __('Contact us').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-5xl px-5 py-14 sm:px-6 sm:py-20">

        <p class="eyebrow">{{ __('Contact us') }}</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ __('Talk to us') }}</h1>
        <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-muted">
            {{ __('Ask about a scent, a shade, or where your order is. We reply to everything.') }}
        </p>

        <div class="mt-10 grid gap-10 lg:grid-cols-[1fr_20rem] lg:gap-16">

            <form method="POST" action="{{ route('contact.send') }}" class="space-y-5">
                @csrf

                @if (session('status'))
                    <p class="rounded-lg border border-accent-fill/30 bg-surface-2 px-4 py-3 text-sm text-accent">
                        {{ session('status') }}
                    </p>
                @endif

                <div>
                    <label for="name" class="eyebrow block">{{ __('Your name') }}</label>
                    <input id="name" name="name" type="text" required maxlength="120"
                           value="{{ old('name') }}"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="eyebrow block">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" required maxlength="190"
                           value="{{ old('email') }}"
                           {{-- dir=ltr because an email address is never RTL, and it
                                renders backwards inside an Arabic form without it. --}}
                           dir="ltr"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="message" class="eyebrow block">{{ __('Message') }}</label>
                    <textarea id="message" name="message" rows="6" required maxlength="2000"
                              class="mt-2 w-full resize-y rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                     outline-none focus:border-accent-fill">{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="w-full rounded-full bg-accent-fill px-8 py-4 text-xs font-semibold uppercase
                               tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90 sm:w-auto">
                    {{ __('Send message') }}
                </button>
            </form>

            <aside class="space-y-8">
                <div>
                    <p class="eyebrow">{{ __('Follow us') }}</p>
                    <div class="mt-4"><x-socials /></div>
                </div>

                <div>
                    <p class="eyebrow">{{ __('Delivering to') }}</p>
                    <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                        @foreach (config('amanelle.markets') as $market)
                            <li>{{ $market['name'] }} · {{ $market['currency'] }}</li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <p class="eyebrow">{{ __('Fastest reply') }}</p>
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                        {{ __('Message us on Instagram — that is where we are most of the day.') }}
                    </p>
                </div>
            </aside>
        </div>
    </section>
@endsection
