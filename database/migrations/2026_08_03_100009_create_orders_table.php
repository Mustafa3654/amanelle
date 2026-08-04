<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();

            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->text('shipping_address');
            $table->string('city')->nullable();
            $table->string('market', 2)->default('LB');

            $table->enum('status', [
                'pending', 'processing', 'shipped', 'delivered', 'cancelled',
            ])->default('pending');

            /*
             * Totals are held in the base currency so reporting stays
             * comparable, but the currency and rate the customer actually saw
             * are snapshotted. The LBP rate moves; without this an old order
             * would silently re-price itself every time the rate is edited.
             */
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('display_currency', 3)->default('USD');
            $table->decimal('display_rate', 18, 6)->default(1);

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            /*
             * Stock bookkeeping guards. Marking an order delivered twice must
             * not deduct twice, and releasing a cancelled order twice must not
             * hand back phantom stock — so each transition records that it ran
             * rather than trusting the status alone.
             */
            $table->timestamp('stock_reserved_at')->nullable();
            $table->timestamp('stock_fulfilled_at')->nullable();
            $table->timestamp('stock_released_at')->nullable();

            // Pending orders hold stock hostage. Past this, a scheduled job
            // releases the reservation and puts the units back on sale.
            $table->timestamp('reservation_expires_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('reservation_expires_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // nullOnDelete, not cascade: deleting a discontinued product must
            // never erase the history of what someone bought.
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Snapshots. What the customer bought is a fact about that moment,
            // not a live lookup that changes when the catalogue is edited.
            $table->string('sku');
            $table->string('product_name');
            $table->string('variant_label')->nullable();

            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);

            $table->timestamps();

            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
