@php
    $locales = config('amanelle.locales');
    $current = app()->getLocale();

    // Main pages only. Categories live on the shop page, where a customer has
    // already decided to browse.
    $pages = [
        route('shop') => __('Shop'),
        route('about') => __('About us'),
        route('contact') => __('Contact us'),
    ];
@endphp

{{-- Sticky, like every fragrance retailer worth copying: on a phone the bag
     and the menu should never be more than a thumb away, and a 68px bar is
     cheap to keep on screen. --}}
<header x-data="{ open: false }"
        class="sticky top-0 z-40 border-b border-hairline bg-surface/95 backdrop-blur supports-[backdrop-filter]:bg-surface/80">
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:gap-6 sm:px-6">

        <a href="{{ route('home') }}" class="wordmark text-base text-accent sm:text-lg" aria-label="{{ config('app.name') }}">
            AMANELLE
        </a>

        <nav class="hidden items-center gap-6 md:flex" aria-label="{{ __('Main') }}">
            @foreach ($pages as $url => $label)
                <a href="{{ $url }}"
                   @class(['text-sm hover:text-accent', 'text-accent' => url()->current() === $url])>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- ms-auto, not ml-auto: in RTL the controls belong on the left edge. --}}
        <div class="ms-auto flex items-center gap-1 sm:gap-2">

            {{-- Utility controls are desktop-only up here. On a phone they move
                 into the sheet, so the bar keeps one row instead of two. --}}
            <div class="hidden items-center gap-1 md:flex">
                <x-currency-switcher />

                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <input type="hidden" name="locale" value="{{ $current === 'ar' ? 'en' : 'ar' }}">
                    <button type="submit" class="rounded-full px-3 py-1.5 text-xs hover:bg-surface-2"
                            lang="{{ $current === 'ar' ? 'en' : 'ar' }}">
                        {{ $current === 'ar' ? $locales['en']['name'] : $locales['ar']['name'] }}
                    </button>
                </form>

                <x-theme-toggle />
            </div>

            <livewire:cart-button />

            <button type="button"
                    {{-- .stop, or this same click also counts as "outside" the
                         sheet and closes it in the same event cycle. --}}
                    @click.stop="open = ! open"
                    :aria-expanded="open"
                    aria-controls="mobile-menu"
                    class="rounded-full p-2 hover:bg-surface-2 md:hidden"
                    aria-label="{{ __('Menu') }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path x-show="! open" d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
                    <path x-show="open" x-cloak d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Sheet rather than a full-screen overlay: three destinations do not
         warrant taking over the page, and the catalogue stays visible behind. --}}
    <div id="mobile-menu"
         x-show="open"
         x-cloak
         x-transition.origin.top
         @click.outside="open = false"
         @keydown.escape.window="open = false"
         class="absolute inset-x-0 top-full z-30 border-b border-hairline bg-surface px-4 py-3 shadow-lg md:hidden">

        <nav class="grid gap-1" aria-label="{{ __('Main') }}">
            @foreach ($pages as $url => $label)
                <a href="{{ $url }}"
                   @class([
                       'rounded-lg px-3 py-3 text-sm',
                       'bg-surface-2 text-accent' => url()->current() === $url,
                       'hover:bg-surface-2' => url()->current() !== $url,
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="mt-3 flex items-center gap-2 border-t border-hairline pt-3">
            <x-currency-switcher />

            <form method="POST" action="{{ route('locale.switch') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $current === 'ar' ? 'en' : 'ar' }}">
                <button type="submit" class="rounded-full bg-surface-2 px-3 py-2 text-xs"
                        lang="{{ $current === 'ar' ? 'en' : 'ar' }}">
                    {{ $current === 'ar' ? $locales['en']['name'] : $locales['ar']['name'] }}
                </button>
            </form>

            <x-theme-toggle class="ms-auto" />
        </div>
    </div>
</header>
