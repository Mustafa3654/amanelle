<button type="button"
        x-data
        @click="
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('amanelle-theme', dark ? 'dark' : 'light');
            $el.setAttribute('aria-pressed', dark);
        "
        {{ $attributes->merge(['class' => 'rounded-full p-2 hover:bg-surface-2']) }}
        aria-label="{{ __('Dark mode') }}">
    <svg class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <svg class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" stroke-linecap="round"/>
    </svg>
</button>
