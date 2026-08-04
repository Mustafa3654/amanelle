@extends('layouts.app')

@section('title', $category->name.' — '.config('app.name'))

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-16">
        <p class="eyebrow">{{ __('Shop') }}</p>
        <h1 class="mt-2 font-display text-2xl sm:text-3xl">{{ $category->name }}</h1>

        <x-category-nav :current="$category->slug" class="mt-7" />

        @if ($products->isEmpty())
            <p class="mt-10 text-sm text-ink-muted">{{ __('Nothing here yet — check back soon.') }}</p>
        @else
            <div class="mt-10 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-12">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
