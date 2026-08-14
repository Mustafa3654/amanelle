@php
    $locale = app()->getLocale();
    $dir = in_array($locale, config('amanelle.rtl_locales', ['ar'])) ? 'rtl' : 'ltr';
    $activeTheme = \App\Models\Theme::active();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        // Almost all of Amanelle's traffic arrives from a link shared on
        // Instagram or WhatsApp. Those apps fetch the page and build a preview
        // card from these tags; without them a shared link is bare grey text.
        $metaTitle = trim($__env->yieldContent('title')) ?: config('app.name');
        $metaDescription = trim($__env->yieldContent('description'))
            ?: __('Original Gulf fragrance and authentic cosmetics, delivered across Lebanon.');
        $metaImage = trim($__env->yieldContent('image')) ?: asset('images/logo.jpeg');
    @endphp

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_LB' : 'en_US' }}">

    {{-- summary_large_image, not summary: a small square thumbnail wastes the
         product photography these links exist to show off. --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">

    <meta name="theme-color" content="{{ $activeTheme?->surface ?? '#0d0b09' }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.jpeg') }}">

    {{-- Runs before first paint so the page never flashes light then snaps to
         dark. Reads the stored choice, falls back to the OS. Deliberately
         inline and blocking — deferring it is the flash. --}}
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('amanelle-theme');
                var dark = stored ? stored === 'dark'
                    : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
            } catch (e) {
                /* private mode / storage disabled — fall through to light */
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @if ($dir === 'rtl')
        <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    @else
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface text-ink antialiased" data-season-effect="{{ $activeTheme?->effect ?? 'none' }}" style="--surface: {{ $activeTheme?->surface ?? '#faf7f2' }}; --surface-2: {{ $activeTheme?->surface_2 ?? '#f2ece1' }}; --ink: {{ $activeTheme?->ink ?? '#1a1612' }}; --accent: {{ $activeTheme?->accent ?? '#8c6a3a' }}; --accent-fill: {{ $activeTheme?->accent_fill ?? '#c9a96e' }}; --accent-soft: {{ $activeTheme?->accent_soft ?? '#e8d5a3' }};">
    @if ($activeTheme?->greeting || $activeTheme?->banner_image)
        <div class="seasonal-banner" role="status" @if ($activeTheme->banner_image) style="background-image:linear-gradient(90deg,rgba(13,11,9,.75),rgba(13,11,9,.35)),url('{{ Storage::disk('public')->url($activeTheme->banner_image) }}')" @endif>
            {{ $activeTheme->greeting }}
        </div>
    @endif
    @if (($activeTheme?->effect ?? 'none') !== 'none')
        <div class="seasonal-effect seasonal-effect-{{ $activeTheme->effect }}" aria-hidden="true"></div>
    @endif
    @include('partials.header')

    <main>
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
