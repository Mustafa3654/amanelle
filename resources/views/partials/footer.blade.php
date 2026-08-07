<footer class="mt-24 border-t border-hairline">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div class="flex flex-col gap-10 md:flex-row md:items-start md:justify-between">

            <div class="max-w-xs">
                <p class="wordmark text-base text-accent">AMANELLE</p>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                    {{ __('Original Gulf fragrance and authentic cosmetics, delivered across Lebanon.') }}
                </p>

                <div class="mt-6">
                    <x-socials />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 text-sm sm:grid-cols-3 sm:gap-12">
                <div>
                    <p class="eyebrow">{{ __('Shop') }}</p>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="{{ route('category', 'perfumes') }}" class="hover:text-accent">{{ __('Perfumes') }}</a></li>
                        <li><a href="{{ route('category', 'skincare') }}" class="hover:text-accent">{{ __('Skincare') }}</a></li>
                        <li><a href="{{ route('category', 'makeup') }}" class="hover:text-accent">{{ __('Makeup') }}</a></li>
                        <li><a href="{{ route('category', 'gift-sets') }}" class="hover:text-accent">{{ __('Gift sets') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="eyebrow">{{ __('Company') }}</p>
                    <ul class="mt-4 space-y-2.5">
                        <li><a href="{{ route('about') }}" class="hover:text-accent">{{ __('About us') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-accent">{{ __('Contact us') }}</a></li>
                        <li><a href="{{ route('track') }}" class="hover:text-accent">{{ __('Track your order') }}</a></li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <p class="eyebrow">{{ __('Delivering to') }}</p>
                    <ul class="mt-4 space-y-2.5 text-ink-muted">
                        @foreach (config('amanelle.markets') as $code => $market)
                            <li>{{ $market['name'] }} · {{ $market['currency'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <p class="eyebrow mt-12 border-t border-hairline pt-6">
            © {{ date('Y') }} {{ config('app.name') }} · {{ __('All rights reserved') }}
        </p>
    </div>
</footer>
