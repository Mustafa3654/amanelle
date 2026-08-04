<?php

use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->refreshCount();
    }

    /**
     * Any component that changes the cart dispatches `cart-updated`, so the
     * badge stays right without every one of them knowing this exists.
     */
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        $this->count = collect(session('cart', []))->sum('quantity');
    }
};
?>

<div>
    <a href="{{ route('cart') }}"
       class="relative flex items-center rounded-full p-2 hover:bg-surface-2"
       aria-label="{{ __('Cart') }}">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 6h18M16 10a4 4 0 0 1-8 0" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        @if ($count > 0)
            {{-- -end/-start rather than -right/-left: the badge follows the
                 icon's trailing corner when the page flips to RTL. --}}
            <span class="absolute -top-0.5 -end-0.5 flex size-4 items-center justify-center
                         rounded-full bg-accent-fill text-[10px] font-semibold text-[#0d0b09]">
                {{ $count }}
            </span>
        @endif
    </a>
</div>
