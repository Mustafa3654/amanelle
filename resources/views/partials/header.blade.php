@php
    $locales = config('amanelle.locales');
    $current = app()->getLocale();
@endphp

<header class="border-b border-hairline">
    <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-4 sm:px-6">

        <a href="{{ route('home') }}" class="wordmark text-lg text-accent" aria-label="{{ config('app.name') }}">
            AMANELLE
        </a>

        <nav class="hidden items-center gap-6 md:flex" aria-label="{{ __('Shop') }}">
            <a href="{{ route('category', 'perfumes') }}" class="text-sm hover:text-accent">{{ __('Perfumes') }}</a>
            <a href="{{ route('category', 'skincare') }}" class="text-sm hover:text-accent">{{ __('Skincare') }}</a>
            <a href="{{ route('category', 'makeup') }}" class="text-sm hover:text-accent">{{ __('Makeup') }}</a>
            <a href="{{ route('category', 'gift-sets') }}" class="text-sm hover:text-accent">{{ __('Gift sets') }}</a>
        </nav>

        {{-- ms-auto, not ml-auto: in RTL the controls belong on the left edge,
             and a physical margin would pin them to the wrong side. --}}
        <div class="ms-auto flex items-center gap-2">

            {{-- Locale switch posts rather than links, so the choice is stored
                 against the session and survives the next visit. --}}
            <form method="POST" action="{{ route('locale.switch') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $current === 'ar' ? 'en' : 'ar' }}">
                <button type="submit"
                        class="rounded-full px-3 py-1.5 text-xs hover:bg-surface-2"
                        lang="{{ $current === 'ar' ? 'en' : 'ar' }}">
                    {{ $current === 'ar' ? $locales['en']['name'] : $locales['ar']['name'] }}
                </button>
            </form>

            <button type="button"
                    x-data
                    @click="
                        const dark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('amanelle-theme', dark ? 'dark' : 'light');
                        $el.setAttribute('aria-pressed', dark);
                    "
                    :aria-pressed="document.documentElement.classList.contains('dark')"
                    class="rounded-full p-2 hover:bg-surface-2"
                    aria-label="{{ __('Dark mode') }}">
                <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke-linecap="round"/>
                </svg>
            </button>

            <livewire:cart-button />
        </div>
    </div>
</header>
