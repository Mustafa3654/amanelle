@extends('layouts.app')

@section('title', __('Order :number', ['number' => $order->number]).' — '.config('app.name'))

@php
    // Re-formats using the currency and rate snapshotted on the order, not
    // today's rate, so the confirmation always shows what was agreed.
    $show = fn (float $base) => number_format($base * (float) $order->display_rate, $order->display_currency === 'LBP' ? 0 : 2)
        .' '.($order->display_currency === 'LBP' ? 'ل.ل' : '$');
@endphp

@section('content')
    <section class="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-20">

        <div class="text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-full border border-accent-fill/40">
                <svg class="size-6 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <h1 class="mt-5 font-display text-2xl">{{ __('Thank you, :name', ['name' => $order->customer_name]) }}</h1>

            <p class="mt-3 text-sm leading-relaxed text-ink-muted">
                {{ __('We will call you on :phone to confirm before it ships.', ['phone' => $order->customer_phone]) }}
            </p>

            <p class="eyebrow mt-5">{{ __('Order') }} <span dir="ltr">{{ $order->number }}</span></p>
        </div>

        {{-- Doubles as the tracking page, so where the order has got to is the
             thing someone returning actually came for. --}}
        @php
            $steps = ['pending' => __('Pending'), 'processing' => __('Processing'), 'shipped' => __('Shipped'), 'delivered' => __('Delivered')];
            $currentIndex = array_search($order->status, array_keys($steps), true);
        @endphp

        @if ($order->status === 'cancelled')
            <p class="mt-8 rounded-lg border border-red-400/40 bg-red-400/5 px-4 py-3 text-center text-sm text-red-400">
                {{ __('This order was cancelled.') }}
            </p>
        @else
            <ol class="mt-10 flex items-center gap-1" aria-label="{{ __('Order status') }}">
                @foreach ($steps as $key => $label)
                    @php $done = $currentIndex !== false && $loop->index <= $currentIndex; @endphp
                    <li class="flex-1 text-center">
                        <div @class([
                                'h-1 rounded-full',
                                'bg-accent-fill' => $done,
                                'bg-surface-3' => ! $done,
                            ])></div>
                        <span @class([
                                'mt-2 block text-[11px]',
                                'text-accent' => $done,
                                'text-ink-muted' => ! $done,
                            ])>{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        <ul class="mt-10 divide-y divide-hairline border-y border-hairline">
            @foreach ($order->items as $item)
                <li class="flex justify-between gap-4 py-4 text-sm">
                    <span class="min-w-0">
                        <span class="block truncate">{{ $item->product_name }}</span>
                        <span class="text-xs text-ink-muted">
                            {{ $item->variant_label }} × {{ $item->quantity }}
                        </span>
                    </span>
                    <span class="shrink-0">{{ $show((float) $item->line_total) }}</span>
                </li>
            @endforeach
        </ul>

        <dl class="mt-5 space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-ink-muted">{{ __('Subtotal') }}</dt>
                <dd>{{ $show((float) $order->subtotal) }}</dd>
            </div>

            @if ((float) $order->discount_total > 0)
                <div class="flex justify-between text-accent">
                    <dt>{{ __('Discount') }} <span class="text-xs">{{ $order->promo_code }}</span></dt>
                    <dd>−{{ $show((float) $order->discount_total) }}</dd>
                </div>
            @endif

            <div class="flex justify-between">
                <dt class="text-ink-muted">
                    {{ __('Delivery') }}
                    @if ($order->delivery_zone_name)
                        <span class="text-xs">· {{ $order->deliveryZone?->name ?? $order->delivery_zone_name }}</span>
                    @endif
                </dt>
                <dd>
                    @if ((float) $order->shipping_total > 0)
                        {{ $show((float) $order->shipping_total) }}
                    @else
                        <span class="text-accent">{{ __('Free') }}</span>
                    @endif
                </dd>
            </div>

            <div class="flex justify-between border-t border-hairline pt-3">
                <dt class="eyebrow">{{ __('Total') }}</dt>
                <dd class="text-lg text-accent">{{ $show((float) $order->total) }}</dd>
            </div>
        </dl>

        <p class="mt-8 rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-xs leading-relaxed text-ink-muted">
            {{ __('Pay cash on delivery. Keep this order number handy if you message us.') }}
        </p>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('shop') }}"
               class="rounded-full bg-accent-fill px-7 py-3.5 text-center text-xs font-semibold uppercase
                      tracking-[0.18em] text-[#0d0b09]">
                {{ __('Continue shopping') }}
            </a>
            <a href="{{ route('contact') }}"
               class="rounded-full border border-accent/40 px-7 py-3.5 text-center text-xs font-semibold
                      uppercase tracking-[0.18em] text-accent">
                {{ __('Contact us') }}
            </a>
        </div>
    </section>
@endsection
