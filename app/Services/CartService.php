<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\PromoCode;
use Illuminate\Support\Collection;

/**
 * Session-backed cart.
 *
 * Only variant ids and quantities are stored. Prices, names and stock are
 * always resolved live from the catalogue, so a cart left open overnight
 * cannot check out at yesterday's price or against stock that has since gone.
 */
class CartService
{
    public const SESSION_KEY = 'cart';

    /** @var Collection<int, array>|null */
    private ?Collection $resolved = null;

    /** @return array<int, array{variant_id: int, quantity: int}> */
    public function raw(): array
    {
        return session(self::SESSION_KEY, []);
    }

    /**
     * Cart lines with their live variant attached. Lines whose variant has
     * been deleted or deactivated are dropped rather than rendered broken.
     *
     * @return Collection<int, array{variant: ProductVariant, quantity: int, line_total: float}>
     */
    public function lines(): Collection
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $raw = $this->raw();

        if ($raw === []) {
            return $this->resolved = collect();
        }

        $variants = ProductVariant::with(['product.brand', 'inventories'])
            ->where('is_active', true)
            ->findMany(array_column($raw, 'variant_id'))
            ->keyBy('id');

        return $this->resolved = collect($raw)
            ->filter(fn (array $line) => $variants->has($line['variant_id']))
            ->map(function (array $line) use ($variants) {
                $variant = $variants[$line['variant_id']];

                return [
                    'variant' => $variant,
                    'quantity' => $line['quantity'],
                    'line_total' => (float) $variant->price * $line['quantity'],
                ];
            })
            ->values();
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function add(int $variantId, int $quantity = 1): array
    {
        $variant = ProductVariant::with('inventories')->where('is_active', true)->find($variantId);

        if (! $variant) {
            return ['ok' => false, 'message' => __('That item is no longer available.')];
        }

        $cart = $this->raw();
        $existing = $cart[$variantId]['quantity'] ?? 0;
        $wanted = $existing + $quantity;

        // Checked here for a decent message, and again atomically at checkout.
        // This one is a courtesy; the reservation is the real gate.
        $available = $variant->availableIn(config('amanelle.default_market'));

        if ($wanted > $available) {
            return [
                'ok' => false,
                'message' => $available === 0
                    ? __('Out of stock')
                    : __('Only :count left', ['count' => $available]),
            ];
        }

        $cart[$variantId] = ['variant_id' => $variantId, 'quantity' => $wanted];

        $this->write($cart);

        return ['ok' => true, 'message' => __('Added to your cart')];
    }

    public function setQuantity(int $variantId, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity < 1) {
            unset($cart[$variantId]);
        } elseif (isset($cart[$variantId])) {
            $cart[$variantId]['quantity'] = $quantity;
        }

        $this->write($cart);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);

        $this->write($cart);
    }

    public function clear(): void
    {
        $this->write([]);
        $this->removePromo();
    }

    public function count(): int
    {
        return collect($this->raw())->sum('quantity');
    }

    public function subtotal(): float
    {
        return (float) $this->lines()->sum('line_total');
    }

    public const PROMO_KEY = 'promo_code';

    /**
     * Re-resolved from the database on every read, never trusted from the
     * session. Only the code string is stored, so a promo that expires or is
     * switched off stops applying immediately — including on carts that were
     * already open when it changed.
     */
    public function promo(): ?PromoCode
    {
        $code = session(self::PROMO_KEY);

        if (! $code) {
            return null;
        }

        $promo = PromoCode::usable()->where('code', strtoupper($code))->first();

        return $promo?->rejectionReason($this->subtotal()) === null ? $promo : null;
    }

    /** @return array{ok: bool, message: string} */
    public function applyPromo(string $code): array
    {
        $promo = PromoCode::where('code', strtoupper(trim($code)))->first();

        if (! $promo) {
            return ['ok' => false, 'message' => __('We do not recognise that code.')];
        }

        if ($reason = $promo->rejectionReason($this->subtotal())) {
            return ['ok' => false, 'message' => $reason];
        }

        session()->put(self::PROMO_KEY, $promo->code);

        return ['ok' => true, 'message' => __(':code applied', ['code' => $promo->code])];
    }

    public function removePromo(): void
    {
        session()->forget(self::PROMO_KEY);
    }

    public function discount(): float
    {
        return $this->promo()?->discountFor($this->subtotal()) ?? 0.0;
    }

    public function total(): float
    {
        return round($this->subtotal() - $this->discount(), 2);
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    private function write(array $cart): void
    {
        session()->put(self::SESSION_KEY, $cart);

        // The resolved cache is per-request; a write inside the same request
        // must not serve the pre-write lines back to the view.
        $this->resolved = null;
    }
}
