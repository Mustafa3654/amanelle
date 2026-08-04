@extends('layouts.app')

@section('title', $product->name.' — '.config('app.name'))

@php
    $market = config('amanelle.default_market');
    $variants = $product->variants->where('is_active', true)->sortBy('sort_order')->values();
    $reference = $product->references->first();

    // Serialised for the selector: price, stock and shade travel together so
    // switching a variant updates all three without a round trip. Prices are
    // pre-formatted server-side so the client never has to know the rate.
    $payload = $variants->map(fn ($v) => [
        'id' => $v->id,
        'label' => $v->label(),
        'hex' => $v->shade_hex,
        'price' => \App\Support\Money::format((float) $v->price),
        'was' => $v->compare_at_price
            ? \App\Support\Money::format((float) $v->compare_at_price)
            : null,
        'stock' => $v->availableIn($market),
    ])->values();
@endphp

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
        <div class="grid gap-10 lg:grid-cols-2 lg:gap-16">

            <div class="relative aspect-square overflow-hidden rounded-xl bg-surface-2">
                {{-- The selected shade washes the plate behind the product, so
                     picking a colour changes the room around it. --}}
                <div x-data
                     class="absolute inset-0 transition-colors duration-500"
                     :style="$store.pdp.hex ? `background: radial-gradient(circle at 50% 35%, ${$store.pdp.hex}33, transparent 70%)` : ''">
                </div>
                <div class="relative size-full">
                    <x-product-placeholder :product="$product" />
                </div>
            </div>

            <div x-data="{
                    variants: {{ $payload->toJson() }},
                    init() {
                        Alpine.store('pdp').select(this.variants[0]);
                    }
                 }">

                <p class="eyebrow">{{ $product->brand?->name }}</p>
                <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ $product->name }}</h1>

                @if ($reference)
                    <p class="mt-3 inline-flex items-center gap-2 rounded-full border border-accent-fill/30 px-3 py-1.5 text-xs text-accent">
                        {{ __('Inspired by') }} {{ $reference->displayName() }}
                    </p>
                @endif

                <p class="mt-5 text-sm leading-relaxed text-ink-muted">{{ $product->short_description }}</p>

                <p class="mt-6 text-xl">
                    <span class="text-accent" x-text="$store.pdp.priceLabel"></span>
                    <s class="ms-2 text-sm text-ink-muted" x-show="$store.pdp.was" x-text="$store.pdp.wasLabel"></s>
                </p>

                @if ($product->type === 'fragrance' && $product->longevity)
                    <dl class="mt-6 grid grid-cols-2 gap-4 border-y border-hairline py-5 text-sm">
                        <div>
                            <dt class="eyebrow">{{ __('Longevity') }}</dt>
                            <dd class="mt-1.5 flex gap-1" aria-label="{{ $product->longevity }}/5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="h-1 w-5 rounded-full {{ $i <= $product->longevity ? 'bg-accent-fill' : 'bg-surface-3' }}"></span>
                                @endfor
                            </dd>
                        </div>
                        <div>
                            <dt class="eyebrow">{{ __('Projection') }}</dt>
                            <dd class="mt-1.5 flex gap-1" aria-label="{{ $product->projection }}/5">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="h-1 w-5 rounded-full {{ $i <= $product->projection ? 'bg-accent-fill' : 'bg-surface-3' }}"></span>
                                @endfor
                            </dd>
                        </div>
                    </dl>
                @endif

                <fieldset class="mt-7">
                    <legend class="eyebrow">
                        {{ $variants->first()?->shade_hex ? __('Shade') : __('Size') }}
                    </legend>

                    <div class="mt-3 flex flex-wrap gap-2.5">
                        <template x-for="variant in variants" :key="variant.id">
                            <button type="button"
                                    @click="$store.pdp.select(variant)"
                                    :disabled="variant.stock === 0"
                                    :class="$store.pdp.id === variant.id
                                        ? 'border-accent-fill text-accent'
                                        : 'border-hairline hover:border-accent-fill/50'"
                                    class="flex items-center gap-2 rounded-full border px-4 py-2.5 text-xs
                                           transition disabled:cursor-not-allowed disabled:opacity-40">
                                <span x-show="variant.hex"
                                      class="size-4 rounded-full ring-1 ring-hairline"
                                      :style="`background-color: ${variant.hex}`"></span>
                                <span x-text="variant.label"></span>
                            </button>
                        </template>
                    </div>
                </fieldset>

                <p class="mt-4 text-xs" :class="$store.pdp.stock > 0 ? 'text-ink-muted' : 'text-red-400'"
                   x-text="$store.pdp.stockLabel"></p>

                {{-- Inline button from sm up. On a phone the sticky bar below
                     does this job, and two add-to-cart buttons on one screen
                     is a way to make people hesitate. --}}
                <button type="button"
                        :disabled="$store.pdp.stock === 0"
                        class="mt-7 hidden rounded-full bg-accent-fill px-8 py-4 text-xs font-semibold
                               uppercase tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90
                               disabled:cursor-not-allowed disabled:opacity-40 sm:inline-block">
                    {{ __('Add to cart') }}
                </button>

                @if ($product->brand?->is_authorised_stockist)
                    <p class="mt-5 flex items-center gap-2 text-xs text-ink-muted">
                        <svg class="size-4 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M12 2 4 6v6c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6Z" stroke-linejoin="round"/>
                            <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ __('100% authentic') }}
                    </p>
                @endif
            </div>
        </div>
    </section>

    {{-- Sticky purchase bar, the pattern every mobile fragrance site uses: the
         price and the action stay reachable however far down the page you are.
         pb-safe keeps it clear of the iOS home indicator. --}}
    <div x-data
         class="fixed inset-x-0 bottom-0 z-40 border-t border-hairline bg-surface/95 px-4 py-3
                pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur sm:hidden">
        <div class="flex items-center gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm text-accent" x-text="$store.pdp.priceLabel"></p>
                <p class="truncate text-[11px]"
                   :class="$store.pdp.stock > 0 ? 'text-ink-muted' : 'text-red-400'"
                   x-text="$store.pdp.stockLabel"></p>
            </div>

            <button type="button"
                    :disabled="$store.pdp.stock === 0"
                    class="ms-auto shrink-0 rounded-full bg-accent-fill px-7 py-3.5 text-xs font-semibold
                           uppercase tracking-[0.18em] text-[#0d0b09] transition
                           disabled:cursor-not-allowed disabled:opacity-40">
                {{ __('Add to cart') }}
            </button>
        </div>
    </div>

    {{-- Clears the sticky bar so the footer is never trapped underneath it. --}}
    <div class="h-20 sm:hidden" aria-hidden="true"></div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('pdp', {
            id: null, hex: null, stock: 0, was: null,
            priceLabel: '', wasLabel: '', stockLabel: '',

            select(variant) {
                if (! variant) return;

                this.id = variant.id;
                this.hex = variant.hex;
                this.stock = variant.stock;
                this.was = variant.was;
                this.priceLabel = variant.price;
                this.wasLabel = variant.was ?? '';
                this.stockLabel = variant.stock === 0
                    ? @js(__('Out of stock'))
                    : variant.stock <= 5
                        ? @js(__('Only :count left')).replace(':count', variant.stock)
                        : @js(__('In stock'));
            },
        });
    });
</script>
@endpush
