@props(['current' => null])

@php
    $categories = \App\Models\Category::active()
        ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
        ->orderBy('sort_order')
        ->get();
@endphp

{{-- Shown on the shop page and on every category page. Without it, switching
     from Perfumes to Skincare means going back first. --}}
<nav {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} aria-label="{{ __('Shop by category') }}">
    <a href="{{ route('shop') }}"
       @class([
           'rounded-full border px-4 py-2 text-xs transition',
           'border-accent-fill bg-accent-fill text-[#0d0b09]' => $current === null,
           'border-hairline bg-surface-2 hover:border-accent-fill/50 hover:text-accent' => $current !== null,
       ])>
        {{ __('All') }}
    </a>

    @foreach ($categories as $category)
        @php $active = $current === $category->slug; @endphp
        <a href="{{ route('category', $category->slug) }}"
           @if ($active) aria-current="page" @endif
           @class([
               'rounded-full border px-4 py-2 text-xs transition',
               'border-accent-fill bg-accent-fill text-[#0d0b09]' => $active,
               'border-hairline bg-surface-2 hover:border-accent-fill/50 hover:text-accent' => ! $active,
           ])>
            {{ $category->name }}
            <span @class(['text-[#0d0b09]/60' => $active, 'text-ink-muted' => ! $active])>
                {{ $category->products_count }}
            </span>
        </a>
    @endforeach
</nav>
