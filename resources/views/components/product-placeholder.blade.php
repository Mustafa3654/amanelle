@props(['product'])

@php
    /*
     * Stand-in art until real photography is uploaded. Rather than a grey box,
     * each product gets a stable tint derived from its slug and a silhouette
     * matching its type, so a listing reads as designed instead of broken.
     *
     * crc32 (not random) keeps a product's colour identical on every render.
     */
    $palette = ['#c9a96e', '#dcae96', '#f2c6ce', '#b98b6d', '#e8d5a3'];
    $tint = $product->variants->firstWhere('shade_hex')?->shade_hex
        ?? $palette[crc32($product->slug) % count($palette)];
@endphp

<div class="relative size-full overflow-hidden">
    <div class="absolute inset-0"
         style="background:
            radial-gradient(circle at 50% 30%, {{ $tint }}2e, transparent 62%),
            radial-gradient(circle at 20% 90%, {{ $tint }}1a, transparent 55%);"></div>

    <svg viewBox="0 0 100 125" class="relative size-full" fill="none" aria-hidden="true"
         style="color: {{ $tint }}">
        @if ($product->type === 'fragrance')
            {{-- Flacon: shoulders, neck, cap --}}
            <rect x="42" y="26" width="16" height="10" rx="1.5" fill="currentColor" opacity="0.5"/>
            <path d="M38 38h24a6 6 0 0 1 6 6v42a6 6 0 0 1-6 6H38a6 6 0 0 1-6-6V44a6 6 0 0 1 6-6Z"
                  fill="currentColor" opacity="0.18" stroke="currentColor" stroke-opacity="0.45"/>
            <rect x="40" y="58" width="20" height="14" rx="1" fill="currentColor" opacity="0.22"/>
        @elseif ($product->type === 'makeup')
            {{-- Bullet lipstick, partly extended --}}
            <path d="M44 30h12v18H44z" fill="currentColor" opacity="0.55"/>
            <path d="M44 30l12-6v6H44Z" fill="currentColor" opacity="0.75"/>
            <rect x="41" y="48" width="18" height="44" rx="3"
                  fill="currentColor" opacity="0.18" stroke="currentColor" stroke-opacity="0.45"/>
        @else
            {{-- Tube with a shoulder seam --}}
            <rect x="43" y="24" width="14" height="8" rx="1.5" fill="currentColor" opacity="0.5"/>
            <path d="M37 34h26l-3 54a6 6 0 0 1-6 5H46a6 6 0 0 1-6-5l-3-54Z"
                  fill="currentColor" opacity="0.18" stroke="currentColor" stroke-opacity="0.45"/>
            <path d="M40 52h20" stroke="currentColor" stroke-opacity="0.3"/>
        @endif
    </svg>

    <span class="wordmark absolute inset-x-0 bottom-3 text-center text-[9px] text-ink-muted/50">
        AMANELLE
    </span>
</div>
