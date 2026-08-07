@php
    $posts = \App\Models\InstagramPost::active()
        ->orderBy('sort_order')
        ->latest('posted_at')
        ->take(8)
        ->get();

    $handle = 'amanelle_beauty';
@endphp

@if ($posts->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 sm:pb-24">
        <div class="flex items-baseline justify-between gap-4">
            <div>
                <p class="eyebrow">{{ __('From our Instagram') }}</p>
                <p class="mt-1 font-display text-lg" dir="ltr">@{{ $handle }}</p>
            </div>

            <a href="https://www.instagram.com/{{ $handle }}" target="_blank" rel="noopener noreferrer"
               class="shrink-0 text-xs text-accent hover:underline">
                {{ __('Follow us') }}
            </a>
        </div>

        {{-- A 9:16 scrolling rail, not a square grid: their content is Reels,
             and a square crop cuts the faces and products out of frame. --}}
        <div class="mt-6 flex gap-3 overflow-x-auto pb-2 sm:gap-4
                    [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($posts as $post)
                <a href="{{ $post->permalink }}" target="_blank" rel="noopener noreferrer"
                   class="group relative w-40 shrink-0 overflow-hidden rounded-lg bg-surface-2 sm:w-48"
                   style="aspect-ratio: 9 / 16;"
                   aria-label="{{ $post->caption ?: __('View on Instagram') }}">

                    @if ($post->image_path)
                        <img src="{{ Storage::url($post->image_path) }}"
                             alt="{{ $post->caption }}"
                             loading="lazy"
                             decoding="async"
                             class="size-full object-cover transition duration-500 group-hover:scale-[1.04]">
                    @else
                        <div class="grid size-full place-items-center">
                            <span class="wordmark text-[10px] text-ink-muted/50">AMANELLE</span>
                        </div>
                    @endif

                    @if ($post->is_video)
                        <span class="absolute end-2 top-2 grid size-6 place-items-center rounded-full bg-black/45 backdrop-blur"
                              aria-hidden="true">
                            <svg class="size-3 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </span>
                    @endif

                    @if ($post->caption)
                        <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2.5
                                     text-[11px] leading-snug text-white line-clamp-2">
                            {{ $post->caption }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
