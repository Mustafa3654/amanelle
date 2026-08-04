@php
    $currencies = \App\Support\Money::currencies();
    $current = \App\Support\Money::current();
@endphp

@if ($currencies->count() > 1 && $current)
    <form method="POST" action="{{ route('currency.switch') }}">
        @csrf
        {{-- Two currencies, so a toggle rather than a dropdown: one tap, and
             the label always shows what you would switch *to*. --}}
        @php
            $next = $currencies->firstWhere('code', '!=', $current->code) ?? $current;
        @endphp
        <input type="hidden" name="currency" value="{{ $next->code }}">
        <button type="submit"
                class="rounded-full px-3 py-1.5 text-xs hover:bg-surface-2"
                title="{{ __('Switch to :code', ['code' => $next->code]) }}">
            {{ $current->code }}
        </button>
    </form>
@endif
