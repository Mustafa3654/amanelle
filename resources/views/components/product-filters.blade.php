@props(['filters' => [], 'action' => null, 'total' => 0])

@php
    $action ??= url()->current();
    $brands = \App\Models\Brand::active()->orderBy('sort_order')->get();
    $active = \App\Support\ProductQuery::isFiltered($filters);
@endphp

{{-- A plain GET form: filters end up in the URL, so a filtered listing can be
     shared, bookmarked and re-entered with the back button. --}}
<form method="GET" action="{{ $action }}" x-data="{ open: false }" class="mt-6">

    <div class="flex flex-wrap items-center gap-2">
        <div class="relative min-w-0 flex-1 sm:max-w-xs">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                   placeholder="{{ __('Search a scent, brand, or the one it replaces') }}"
                   class="w-full rounded-full border border-hairline bg-surface-2 py-2.5 pe-4 ps-10 text-sm
                          outline-none focus:border-accent-fill">
            <svg class="pointer-events-none absolute inset-y-0 start-3 my-auto size-4 text-ink-muted"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/>
            </svg>
        </div>

        <select name="sort" onchange="this.form.submit()"
                class="rounded-full border border-hairline bg-surface-2 px-4 py-2.5 text-xs outline-none
                       focus:border-accent-fill">
            @foreach (\App\Support\ProductQuery::SORTS as $value => $label)
                <option value="{{ $value }}" @selected(($filters['sort'] ?? 'featured') === $value)>
                    {{ __($label) }}
                </option>
            @endforeach
        </select>

        <button type="button" @click="open = ! open"
                @class([
                    'rounded-full border px-4 py-2.5 text-xs transition',
                    'border-accent-fill text-accent' => $active,
                    'border-hairline hover:border-accent-fill/50' => ! $active,
                ])>
            {{ __('Filters') }}
        </button>

        <button type="submit" class="rounded-full bg-accent-fill px-5 py-2.5 text-xs font-semibold
                                     uppercase tracking-wider text-[#0d0b09]">
            {{ __('Apply') }}
        </button>

        @if ($active)
            <a href="{{ $action }}" class="text-xs text-ink-muted underline hover:text-accent">
                {{ __('Clear') }}
            </a>
        @endif
    </div>

    <div x-show="open" x-cloak x-transition
         class="mt-4 grid gap-4 rounded-xl border border-hairline bg-surface-2 p-4 sm:grid-cols-2 lg:grid-cols-4">

        <label class="block">
            <span class="eyebrow">{{ __('Brand') }}</span>
            <select name="brand" class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('Any') }}</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->slug }}" @selected(($filters['brand'] ?? '') === $brand->slug)>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="block">
            <span class="eyebrow">{{ __('For') }}</span>
            <select name="gender" class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('Any') }}</option>
                @foreach (['women' => __('Women'), 'men' => __('Men'), 'unisex' => __('Unisex')] as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['gender'] ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </label>

        {{-- "At least", not "exactly": someone after a long-lasting scent
             wants 4 and 5, not only 4. --}}
        <label class="block">
            <span class="eyebrow">{{ __('Longevity') }}</span>
            <select name="longevity" class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('Any') }}</option>
                @for ($i = 3; $i <= 5; $i++)
                    <option value="{{ $i }}" @selected((int) ($filters['longevity'] ?? 0) === $i)>
                        {{ __(':n and above', ['n' => $i]) }}
                    </option>
                @endfor
            </select>
        </label>

        <label class="block">
            <span class="eyebrow">{{ __('Projection') }}</span>
            <select name="projection" class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
                <option value="">{{ __('Any') }}</option>
                @for ($i = 3; $i <= 5; $i++)
                    <option value="{{ $i }}" @selected((int) ($filters['projection'] ?? 0) === $i)>
                        {{ __(':n and above', ['n' => $i]) }}
                    </option>
                @endfor
            </select>
        </label>

        <div class="grid grid-cols-2 gap-2 sm:col-span-2">
            <label class="block">
                <span class="eyebrow">{{ __('Min price') }}</span>
                <input type="number" name="min_price" min="0" step="1" dir="ltr"
                       value="{{ $filters['min_price'] ?? '' }}"
                       class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
            </label>
            <label class="block">
                <span class="eyebrow">{{ __('Max price') }}</span>
                <input type="number" name="max_price" min="0" step="1" dir="ltr"
                       value="{{ $filters['max_price'] ?? '' }}"
                       class="mt-1.5 w-full rounded-lg border border-hairline bg-surface px-3 py-2 text-sm">
            </label>
        </div>

        <label class="flex items-center gap-2 self-end sm:col-span-2">
            <input type="checkbox" name="inspired" value="1" @checked(! empty($filters['inspired']))
                   class="size-4 accent-[#c9a96e]">
            <span class="text-sm">{{ __('Only alternatives to designer scents') }}</span>
        </label>
    </div>
</form>
