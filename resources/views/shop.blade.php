@extends('layouts.app')

@section('title', __('Shop').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16">

        <p class="eyebrow">{{ __('Shop') }}</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ __('Everything we carry') }}</h1>

        <x-category-nav class="mt-7" />

        <x-product-filters :filters="$filters" :action="route('shop')" />

        <p class="mt-6 text-xs text-ink-muted">
            {{ trans_choice('{0}No products match|{1}1 product|[2,*]:count products', $products->total(), ['count' => $products->total()]) }}
        </p>

        @if ($products->isEmpty())
            <p class="mt-8 text-sm text-ink-muted">
                {{ __('Nothing matched. Try fewer filters.') }}
            </p>
        @else
            <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-12">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
