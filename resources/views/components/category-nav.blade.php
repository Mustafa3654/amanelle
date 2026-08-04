@props(['current' => null])

@php
    $categories = \App\Models\Category::active()
        ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
        ->orderBy('sort_order')
        ->get();
@endphp

{{-- One row that scrolls sideways, never wraps. Wrapping pushed the products
     further down on every narrow screen; scrolling costs no vertical space.
     Scrollbar hidden because the partially-visible last pill is the
     affordance. --}}
<nav {{ $attributes->merge([
        'class' => 'flex gap-2 overflow-x-auto pb-1
                    [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden',
    ]) }}
     aria-label="{{ __('Shop by category') }}">
    @foreach ($categories as $category)
        @php $active = $current === $category->slug; @endphp
        <a href="{{ route('category', $category->slug) }}"
           @if ($active) aria-current="page" @endif
           @class([
               'shrink-0 whitespace-nowrap rounded-full border px-4 py-2 text-xs transition',
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
