@extends('layouts.app')

@section('title', __('Shop').' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16">

        <p class="eyebrow">{{ __('Shop') }}</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ __('Everything we carry') }}</h1>

        {{-- Categories live here rather than in the header: by this point the
             customer has decided to browse, and this is where narrowing helps. --}}
        <x-category-nav class="mt-7" />

        <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

        <div class="mt-12">{{ $products->links() }}</div>
    </section>
@endsection
