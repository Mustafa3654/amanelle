<?php

use App\Services\CartService;
use App\Support\Money;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public function updateQuantity(int $variantId, int $quantity): void
    {
        app(CartService::class)->setQuantity($variantId, $quantity);

        $this->dispatch('cart-updated');
    }

    public function remove(int $variantId): void
    {
        app(CartService::class)->remove($variantId);

        $this->dispatch('cart-updated');
    }

    #[On('cart-updated')]
    public function refresh(): void
    {
        // The badge and this panel both listen; re-rendering is the whole job.
    }

    public function with(): array
    {
        $cart = app(CartService::class);

        return [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'market' => config('amanelle.default_market'),
        ];
    }
};
?>

<div>
    @if ($lines->isEmpty())
        <div class="py-8">
            <p class="text-sm text-ink-muted">{{ __('Your cart is empty') }}</p>
            <a href="{{ route('shop') }}"
               class="mt-6 inline-block rounded-full bg-accent-fill px-6 py-3 text-xs font-semibold
                      uppercase tracking-[0.18em] text-[#0d0b09]">
                {{ __('Start shopping') }}
            </a>
        </div>
    @else
        <ul class="divide-y divide-hairline border-y border-hairline">
            @foreach ($lines as $line)
                @php
                    $variant = $line['variant'];
                    $available = $variant->availableIn($market);
                @endphp

                <li class="flex gap-4 py-5" wire:key="line-{{ $variant->id }}">
                    <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-surface-2">
                        <x-product-placeholder :product="$variant->product" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="eyebrow truncate">{{ $variant->product?->brand?->name }}</p>
                        <p class="mt-0.5 truncate text-sm">{{ $variant->product?->name }}</p>

                        <p class="mt-0.5 flex items-center gap-1.5 text-xs text-ink-muted">
                            @if ($variant->shade_hex)
                                <span class="size-3 rounded-full ring-1 ring-hairline"
                                      style="background-color: {{ $variant->shade_hex }}"></span>
                            @endif
                            {{ $variant->label() }}
                        </p>

                        {{-- Quantities are capped at what is actually sellable,
                             so the cart cannot ask for more than checkout will
                             allow and fail at the last step. --}}
                        <div class="mt-2.5 flex items-center gap-3">
                            <div class="flex items-center rounded-full border border-hairline">
                                <button type="button"
                                        wire:click="updateQuantity({{ $variant->id }}, {{ $line['quantity'] - 1 }})"
                                        class="px-3 py-1.5 text-sm hover:text-accent"
                                        aria-label="{{ __('Decrease') }}">−</button>
                                <span class="min-w-6 text-center text-xs">{{ $line['quantity'] }}</span>
                                <button type="button"
                                        wire:click="updateQuantity({{ $variant->id }}, {{ $line['quantity'] + 1 }})"
                                        @disabled($line['quantity'] >= $available)
                                        class="px-3 py-1.5 text-sm hover:text-accent disabled:opacity-30"
                                        aria-label="{{ __('Increase') }}">+</button>
                            </div>

                            <button type="button" wire:click="remove({{ $variant->id }})"
                                    class="text-xs text-ink-muted underline hover:text-accent">
                                {{ __('Remove') }}
                            </button>
                        </div>

                        @if ($available <= 5)
                            <p class="mt-2 text-[11px] text-accent">
                                {{ $available === 0 ? __('Out of stock') : __('Only :count left', ['count' => $available]) }}
                            </p>
                        @endif
                    </div>

                    <p class="shrink-0 text-sm text-accent">
                        {{ Money::format($line['line_total']) }}
                    </p>
                </li>
            @endforeach
        </ul>

        <div class="mt-6 flex items-center justify-between">
            <p class="eyebrow">{{ __('Subtotal') }}</p>
            <p class="text-lg text-accent">{{ Money::format($subtotal) }}</p>
        </div>

        <p class="mt-2 text-xs text-ink-muted">
            {{ __('Shipping is calculated when we confirm your order.') }}
        </p>

        <a href="{{ route('checkout') }}"
           class="mt-6 block rounded-full bg-accent-fill px-8 py-4 text-center text-xs font-semibold
                  uppercase tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90">
            {{ __('Checkout') }}
        </a>
    @endif
</div>
