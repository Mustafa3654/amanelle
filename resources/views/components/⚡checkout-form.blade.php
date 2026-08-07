<?php

use App\Exceptions\InsufficientStockException;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Support\Money;
use Livewire\Component;

new class extends Component
{
    public string $customer_name = '';

    public string $customer_phone = '';

    public string $customer_email = '';

    public string $shipping_address = '';

    public string $city = '';

    public string $notes = '';

    public string $stockError = '';

    public string $promo = '';

    public string $promoMessage = '';

    public bool $promoFailed = false;

    public function applyPromo(): void
    {
        $result = app(CartService::class)->applyPromo($this->promo);

        $this->promoMessage = $result['message'];
        $this->promoFailed = ! $result['ok'];

        if ($result['ok']) {
            $this->promo = '';
        }
    }

    public function removePromo(): void
    {
        app(CartService::class)->removePromo();

        $this->promoMessage = '';
        $this->promoFailed = false;
    }

    public ?int $zoneId = null;

    public function updatedZoneId($value): void
    {
        app(CartService::class)->setZone($value ? (int) $value : null);
    }

    protected function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            // Required, email optional: this market orders by phone and
            // WhatsApp, and demanding an address people do not use costs
            // orders.
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function place(CheckoutService $checkout)
    {
        $data = $this->validate();

        try {
            $order = $checkout->place($data);
        } catch (InsufficientStockException $e) {
            // Someone else took the last one between viewing the cart and
            // pressing the button. The order rolled back; say so plainly and
            // let them adjust rather than failing silently.
            $this->stockError = $e->getMessage();

            return null;
        }

        return $this->redirect(route('order.confirmation', $order->number), navigate: true);
    }

    public function with(): array
    {
        $cart = app(CartService::class);

        // Seeded from the cart so the default zone is preselected on first
        // load without the customer having to touch it.
        $this->zoneId ??= $cart->zone()?->id;

        return [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'discount' => $cart->discount(),
            'shipping' => $cart->shipping(),
            'total' => $cart->total(),
            'appliedPromo' => $cart->promo(),
            'zones' => \App\Models\DeliveryZone::active()->orderBy('sort_order')->get(),
            'currentZone' => $cart->zone(),
        ];
    }
};
?>

<div>
    @if ($lines->isEmpty())
        <p class="text-sm text-ink-muted">{{ __('Your cart is empty') }}</p>
        <a href="{{ route('shop') }}"
           class="mt-6 inline-block rounded-full bg-accent-fill px-6 py-3 text-xs font-semibold
                  uppercase tracking-[0.18em] text-[#0d0b09]">
            {{ __('Start shopping') }}
        </a>
    @else
        <div class="grid gap-10 lg:grid-cols-[1fr_22rem] lg:gap-16">

            <form wire:submit="place" class="space-y-5">
                @if ($stockError)
                    <p class="rounded-lg border border-red-400/40 bg-red-400/5 px-4 py-3 text-sm text-red-400">
                        {{ $stockError }}
                    </p>
                @endif

                <div>
                    <label for="customer_name" class="eyebrow block">{{ __('Your name') }}</label>
                    <input id="customer_name" wire:model="customer_name" type="text"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('customer_name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="customer_phone" class="eyebrow block">{{ __('Phone') }}</label>
                    <input id="customer_phone" wire:model="customer_phone" type="tel" dir="ltr"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('customer_phone') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="customer_email" class="eyebrow block">
                        {{ __('Email') }} <span class="text-ink-muted">{{ __('(optional)') }}</span>
                    </label>
                    <input id="customer_email" wire:model="customer_email" type="email" dir="ltr"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('customer_email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city" class="eyebrow block">{{ __('City') }}</label>
                    <input id="city" wire:model="city" type="text"
                           class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                                  outline-none focus:border-accent-fill">
                    @error('city') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="shipping_address" class="eyebrow block">{{ __('Address') }}</label>
                    <textarea id="shipping_address" wire:model="shipping_address" rows="3"
                              class="mt-2 w-full resize-y rounded-lg border border-hairline bg-surface-2 px-4 py-3
                                     text-sm outline-none focus:border-accent-fill"></textarea>
                    @error('shipping_address') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                @if ($zones->isNotEmpty())
                    <fieldset>
                        <legend class="eyebrow">{{ __('Delivery area') }}</legend>

                        <div class="mt-2 grid gap-2">
                            @foreach ($zones as $zone)
                                @php $zoneFee = $zone->feeFor($subtotal - $discount); @endphp
                                <label @class([
                                        'flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 text-sm transition',
                                        'border-accent-fill bg-accent-fill/5' => (int) $zoneId === $zone->id,
                                        'border-hairline hover:border-accent-fill/50' => (int) $zoneId !== $zone->id,
                                    ])>
                                    <input type="radio" wire:model.live="zoneId" value="{{ $zone->id }}"
                                           name="zoneId"
                                           class="size-4 accent-[#c9a96e]">

                                    <span class="min-w-0 flex-1">
                                        <span class="block">{{ $zone->name }}</span>
                                        @if ($zone->description)
                                            <span class="block text-xs text-ink-muted">{{ $zone->description }}</span>
                                        @endif
                                    </span>

                                    <span class="shrink-0 text-xs {{ $zoneFee > 0 ? 'text-ink-muted' : 'text-accent' }}">
                                        @if ($zoneFee > 0)
                                            {{ Money::format($zoneFee) }}
                                        @else
                                            {{ __('Free') }}
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div>
                    <label for="notes" class="eyebrow block">
                        {{ __('Notes') }} <span class="text-ink-muted">{{ __('(optional)') }}</span>
                    </label>
                    <textarea id="notes" wire:model="notes" rows="2"
                              class="mt-2 w-full resize-y rounded-lg border border-hairline bg-surface-2 px-4 py-3
                                     text-sm outline-none focus:border-accent-fill"></textarea>
                </div>

                <p class="text-xs leading-relaxed text-ink-muted">
                    {{ __('Pay cash on delivery. We will call to confirm your order before it ships.') }}
                </p>

                <button type="submit" wire:loading.attr="disabled"
                        class="w-full rounded-full bg-accent-fill px-8 py-4 text-xs font-semibold uppercase
                               tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90
                               disabled:opacity-50 sm:w-auto">
                    <span wire:loading.remove wire:target="place">{{ __('Place order') }}</span>
                    <span wire:loading wire:target="place">{{ __('Placing…') }}</span>
                </button>
            </form>

            <aside class="rounded-xl border border-hairline bg-surface-2 p-5 lg:sticky lg:top-24 lg:self-start">
                <p class="eyebrow">{{ __('Your order') }}</p>

                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($lines as $line)
                        <li class="flex justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block truncate">{{ $line['variant']->product?->name }}</span>
                                <span class="text-xs text-ink-muted">
                                    {{ $line['variant']->label() }} × {{ $line['quantity'] }}
                                </span>
                            </span>
                            <span class="shrink-0">{{ Money::format($line['line_total']) }}</span>
                        </li>
                    @endforeach
                </ul>

                {{-- Promo sits in the summary, next to the number it changes,
                     rather than buried in the delivery form. --}}
                <div class="mt-5 border-t border-hairline pt-4">
                    @if ($appliedPromo)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-accent-fill/30 px-3 py-2">
                            <span class="min-w-0 text-xs">
                                <span class="font-semibold text-accent">{{ $appliedPromo->code }}</span>
                                <span class="text-ink-muted">−{{ $appliedPromo->label() }}</span>
                            </span>
                            <button type="button" wire:click="removePromo"
                                    class="shrink-0 text-xs text-ink-muted underline hover:text-accent">
                                {{ __('Remove') }}
                            </button>
                        </div>
                    @else
                        <label for="promo" class="eyebrow block">{{ __('Promo code') }}</label>
                        <div class="mt-2 flex gap-2">
                            <input id="promo" wire:model="promo" wire:keydown.enter.prevent="applyPromo"
                                   type="text" dir="ltr" placeholder="{{ __('Enter code') }}"
                                   class="min-w-0 flex-1 rounded-lg border border-hairline bg-surface px-3 py-2.5
                                          text-sm uppercase outline-none focus:border-accent-fill">
                            <button type="button" wire:click="applyPromo"
                                    class="shrink-0 rounded-lg border border-accent-fill/40 px-4 text-xs
                                           font-semibold uppercase tracking-wider text-accent hover:bg-accent-fill/10">
                                {{ __('Apply') }}
                            </button>
                        </div>
                    @endif

                    @if ($promoMessage)
                        <p @class(['mt-2 text-xs', 'text-red-400' => $promoFailed, 'text-accent' => ! $promoFailed])>
                            {{ $promoMessage }}
                        </p>
                    @endif
                </div>

                <dl class="mt-5 space-y-2 border-t border-hairline pt-4 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
                        <dd>{{ Money::format($subtotal) }}</dd>
                    </div>

                    @if ($discount > 0)
                        <div class="flex justify-between text-accent">
                            <dt>{{ __('Discount') }}</dt>
                            <dd>−{{ Money::format($discount) }}</dd>
                        </div>
                    @endif

                    @if ($currentZone)
                        <div class="flex justify-between">
                            <dt class="text-ink-muted">
                                {{ __('Delivery') }}
                                <span class="text-xs">· {{ $currentZone->name }}</span>
                            </dt>
                            <dd>
                                @if ($shipping > 0)
                                    {{ Money::format($shipping) }}
                                @else
                                    <span class="text-accent">{{ __('Free') }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between border-t border-hairline pt-3">
                        <dt class="eyebrow">{{ __('Total') }}</dt>
                        <dd class="text-lg text-accent">{{ Money::format($total) }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    @endif
</div>
