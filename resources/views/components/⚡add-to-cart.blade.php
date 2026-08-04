<?php

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

new class extends Component
{
    public Product $product;

    /** Compact card button vs the full-width PDP button. */
    public bool $compact = false;

    /** Set by the PDP's shade selector; null on a card until one is picked. */
    public ?int $variantId = null;

    public bool $picking = false;

    public string $flash = '';

    public bool $failed = false;

    public function add(?int $variantId = null): void
    {
        $variantId ??= $this->variantId;

        $variants = $this->product->variants->where('is_active', true);

        // One variant means there is nothing to choose — adding straight from
        // the card is the whole point. More than one and we have to ask, or
        // we would be picking a shade on the customer's behalf.
        if (! $variantId && $variants->count() === 1) {
            $variantId = $variants->first()->id;
        }

        if (! $variantId) {
            $this->picking = true;

            return;
        }

        $result = app(CartService::class)->add($variantId);

        $this->flash = $result['message'];
        $this->failed = ! $result['ok'];
        $this->picking = false;

        if ($result['ok']) {
            $this->dispatch('cart-updated');
        }
    }

    public function with(): array
    {
        return [
            'variants' => $this->product->variants
                ->where('is_active', true)
                ->sortBy('sort_order'),
            'market' => config('amanelle.default_market'),
        ];
    }
};
?>

<div class="relative">
    @if ($compact)
        <button type="button"
                wire:click="add"
                wire:loading.attr="disabled"
                class="w-full rounded-full border border-accent-fill/40 py-2.5 text-[11px] font-semibold
                       uppercase tracking-[0.14em] text-accent transition
                       hover:bg-accent-fill hover:text-[#0d0b09] disabled:opacity-50">
            <span wire:loading.remove wire:target="add">
                {{ $variants->count() > 1 ? __('Choose') : __('Add to cart') }}
            </span>
            <span wire:loading wire:target="add">{{ __('Adding…') }}</span>
        </button>
    @else
        <button type="button"
                wire:click="add"
                wire:loading.attr="disabled"
                class="w-full rounded-full bg-accent-fill px-8 py-4 text-xs font-semibold uppercase
                       tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90 disabled:opacity-50">
            <span wire:loading.remove wire:target="add">{{ __('Add to cart') }}</span>
            <span wire:loading wire:target="add">{{ __('Adding…') }}</span>
        </button>
    @endif

    {{-- Inline picker rather than a jump to the PDP: the customer asked not to
         open the product, so choosing a shade should not open it either. --}}
    @if ($picking)
        {{-- w-max, not inset-x-0: constrained to the card's column the shade
             names truncated to "Desert…". It may overhang its card. --}}
        <div class="absolute bottom-full start-0 z-20 mb-2 w-max min-w-full
                    max-w-[calc(100vw-2rem)] rounded-lg border border-hairline
                    bg-surface p-3 shadow-xl">
            <p class="eyebrow mb-2">
                {{ $variants->first()?->shade_hex ? __('Shade') : __('Size') }}
            </p>

            <div class="grid gap-1.5">
                @foreach ($variants as $variant)
                    @php $available = $variant->availableIn($market); @endphp
                    <button type="button"
                            wire:click="add({{ $variant->id }})"
                            @disabled($available === 0)
                            class="flex items-center gap-2 rounded-md px-2 py-2 text-start text-xs
                                   hover:bg-surface-2 disabled:opacity-40">
                        @if ($variant->shade_hex)
                            <span class="size-4 shrink-0 rounded-full ring-1 ring-hairline"
                                  style="background-color: {{ $variant->shade_hex }}"></span>
                        @endif
                        <span class="whitespace-nowrap">{{ $variant->label() }}</span>
                        <span class="ms-auto shrink-0 ps-4 text-ink-muted">
                            {{ $available === 0 ? __('Out of stock') : \App\Support\Money::format((float) $variant->price) }}
                        </span>
                    </button>
                @endforeach
            </div>

            <button type="button" wire:click="$set('picking', false)"
                    class="mt-2 w-full rounded-md py-1.5 text-[11px] text-ink-muted hover:bg-surface-2">
                {{ __('Cancel') }}
            </button>
        </div>
    @endif

    @if ($flash)
        <p wire:key="flash-{{ $flash }}"
           x-data
           x-init="setTimeout(() => $wire.set('flash', ''), 2500)"
           @class([
               'mt-2 text-center text-[11px]',
               'text-red-400' => $failed,
               'text-accent' => ! $failed,
           ])>
            {{ $flash }}
        </p>
    @endif
</div>
