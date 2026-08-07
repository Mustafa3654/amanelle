<?php

use App\Models\Order;
use Livewire\Component;

new class extends Component
{
    public string $number = '';

    public string $phone = '';

    public string $error = '';

    public function find()
    {
        $this->validate([
            'number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:40'],
        ]);

        /*
         * Order number alone is not enough to open someone's order: the
         * numbers are sequential by day and trivially guessable. Requiring the
         * phone number as well means you have to already know the order to
         * look it up.
         *
         * Compared on digits only, so +961 70 000 000 and 03000000 entered
         * loosely still match what was typed at checkout.
         */
        $digits = preg_replace('/\D+/', '', $this->phone);

        $order = Order::where('number', strtoupper(trim($this->number)))->first();

        if (! $order || ! $digits || ! str_ends_with(preg_replace('/\D+/', '', $order->customer_phone), substr($digits, -6))) {
            $this->error = __('We could not find an order with those details.');

            return null;
        }

        // Phone matched, so this session may now view it.
        $order->grantSessionAccess();

        return $this->redirect(route('order.confirmation', $order->number), navigate: true);
    }
};
?>

<div class="mx-auto max-w-md">
    <form wire:submit="find" class="space-y-4">
        @if ($error)
            <p class="rounded-lg border border-red-400/40 bg-red-400/5 px-4 py-3 text-sm text-red-400">
                {{ $error }}
            </p>
        @endif

        <div>
            <label for="number" class="eyebrow block">{{ __('Order number') }}</label>
            <input id="number" wire:model="number" type="text" dir="ltr" placeholder="AMN-260804-0001"
                   class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                          outline-none focus:border-accent-fill">
            @error('number') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="phone" class="eyebrow block">{{ __('Phone') }}</label>
            <input id="phone" wire:model="phone" type="tel" dir="ltr"
                   class="mt-2 w-full rounded-lg border border-hairline bg-surface-2 px-4 py-3 text-sm
                          outline-none focus:border-accent-fill">
            @error('phone') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                class="w-full rounded-full bg-accent-fill px-8 py-4 text-xs font-semibold uppercase
                       tracking-[0.18em] text-[#0d0b09] transition hover:opacity-90">
            {{ __('Find my order') }}
        </button>
    </form>
</div>
