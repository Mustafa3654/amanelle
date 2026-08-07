<?php

namespace App\Models;

use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'display_rate' => 'decimal:6',
            'placed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'stock_reserved_at' => 'datetime',
            'stock_fulfilled_at' => 'datetime',
            'stock_released_at' => 'datetime',
            'reservation_expires_at' => 'datetime',
        ];
    }

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function deliveryZone(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    protected static function booted(): void
    {
        /*
         * Stock follows status automatically, so it cannot drift just because
         * someone changed the dropdown in Filament instead of calling a
         * service method. Both transitions are idempotent — the service checks
         * its own timestamps — so re-saving delivered twice deducts once.
         */
        static::updated(function (self $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $stock = app(StockService::class);

            match ($order->status) {
                'delivered' => $stock->fulfilFor($order),
                'cancelled' => $stock->releaseFor($order),
                default => null,
            };

            $order->notifyCustomerOfStatus();
        });
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ['pending', 'processing', 'shipped']);
    }

    /**
     * Pending orders that have sat past their window. Their units are still
     * reserved and unsellable, so a scheduled job hands them back.
     */
    public function scopeExpiredReservations(Builder $query): void
    {
        $query->where('status', 'pending')
            ->whereNotNull('stock_reserved_at')
            ->whereNull('stock_released_at')
            ->whereNull('stock_fulfilled_at')
            ->where('reservation_expires_at', '<=', now());
    }

    /**
     * Let the customer know where their order is.
     *
     * Wrapped and swallowed: the status change and its stock movement are the
     * real work, and a mail server having a bad day must not undo them.
     */
    public function notifyCustomerOfStatus(): void
    {
        if (! \App\Notifications\OrderStatusChanged::shouldNotifyFor($this->status)) {
            return;
        }

        if (! $this->customer_email) {
            return;
        }

        try {
            \Illuminate\Support\Facades\Notification::route('mail', $this->customer_email)
                ->notify(new \App\Notifications\OrderStatusChanged($this));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Customer status email failed', [
                'order' => $this->number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Let this session view this order.
     *
     * Granted on checkout and on a successful lookup, since order numbers
     * alone are guessable and the page carries a home address.
     */
    public function grantSessionAccess(): void
    {
        $seen = session('viewable_orders', []);
        $seen[] = $this->number;

        session()->put('viewable_orders', array_values(array_unique($seen)));
    }

    public static function nextNumber(): string
    {
        return 'AMN-'.now()->format('ymd').'-'.str_pad((string) (static::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
