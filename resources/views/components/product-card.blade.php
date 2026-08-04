@props(['product'])

@php
    $variant = $product->cheapestVariant();
    $reference = $product->references->first();
    $shades = $product->variants->where('is_active', true)->whereNotNull('shade_hex');
@endphp

<article class="group flex flex-col">
    <a href="{{ route('product', $product->slug) }}" class="block">
        {{-- Square at every size. The 4:5 crop bought a little more bottle and
             cost a lot of scrolling — square fits noticeably more of the
             catalogue per screen, which matters more when browsing. --}}
        <div class="relative aspect-square overflow-hidden rounded-lg bg-surface-2">
            @if ($variant?->image_path)
                <img src="{{ asset('storage/'.$variant->image_path) }}"
                     alt="{{ $product->name }}"
                     loading="lazy"
                     class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
            @else
                <x-product-placeholder :product="$product" />
            @endif

            @if ($reference)
                {{-- Just the house on a phone: "Inspired by Kayali Yum
                     Pistachio Gelato 33" wrapped to three lines over the art. --}}
                <span class="absolute top-2 start-2 max-w-[calc(100%-1rem)] truncate rounded-full
                             bg-surface/90 px-2.5 py-1 text-[10px] text-accent backdrop-blur">
                    <span class="sm:hidden">{{ $reference->designer_house }}</span>
                    <span class="hidden sm:inline">{{ __('Inspired by') }} {{ $reference->displayName() }}</span>
                </span>
            @endif
        </div>
    </a>

    <div class="mt-3 flex flex-1 flex-col">
        <p class="eyebrow truncate">{{ $product->brand?->name }}</p>

        {{-- Clamped to two lines so every card in a row ends at the same
             baseline; ragged heights were what made the grid look broken. --}}
        <h3 class="mt-1 line-clamp-2 min-h-[2.6em] text-sm leading-snug">
            <a href="{{ route('product', $product->slug) }}" class="hover:text-accent">
                {{ $product->name }}
            </a>
        </h3>

        @if ($shades->isNotEmpty())
            <ul class="mt-2 flex items-center gap-1.5" aria-label="{{ __('Shade') }}">
                @foreach ($shades->take(5) as $shade)
                    <li class="size-3.5 rounded-full ring-1 ring-hairline"
                        style="background-color: {{ $shade->shade_hex }}"
                        title="{{ $shade->shade_name }}"></li>
                @endforeach
            </ul>
        @endif

        @if ($product->type === 'fragrance' && $product->longevity)
            {{-- Secondary detail; hidden on phones where it wrapped to two
                 uneven lines and pushed the price out of alignment. --}}
            <p class="mt-2 hidden text-xs text-ink-muted sm:block">
                {{ __('Longevity') }} {{ $product->longevity }}/5 ·
                {{ __('Projection') }} {{ $product->projection }}/5
            </p>
        @endif

        @if ($variant)
            <p class="mt-auto pt-3 text-sm">
                <span class="text-accent">{{ \App\Support\Money::format((float) $variant->price) }}</span>
                @if ($variant->compare_at_price)
                    <s class="ms-2 hidden text-xs text-ink-muted sm:inline">
                        {{ \App\Support\Money::format((float) $variant->compare_at_price) }}
                    </s>
                @endif
            </p>

            <div class="mt-2.5">
                <livewire:add-to-cart :product="$product" :compact="true" :key="'atc-'.$product->id" />
            </div>
        @endif
    </div>
</article>
