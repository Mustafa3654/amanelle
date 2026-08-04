<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();

            // Stored uppercase and compared uppercase, so "welcome10" and
            // "WELCOME10" are the same code — customers type it however they
            // like, usually from a story screenshot.
            $table->string('code')->unique();

            $table->json('description')->nullable();

            $table->enum('type', ['percent', 'fixed']);

            // Percent: 10 means 10%. Fixed: an amount in the base currency
            // (USD), converted for display like every other price.
            $table->decimal('value', 10, 2);

            $table->decimal('min_subtotal', 10, 2)->nullable();

            // A percent discount with no ceiling on a large order can wipe out
            // the margin entirely; this caps the damage.
            $table->decimal('max_discount', 10, 2)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Snapshots, not a live lookup: the code and the amount it took off
            // are facts about this order. Editing or deleting the promo later
            // must not rewrite what a customer already paid.
            $table->string('promo_code')->nullable()->after('shipping_total');
            $table->decimal('discount_total', 12, 2)->default(0)->after('promo_code');
            $table->foreignId('promo_code_id')->nullable()->after('discount_total')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_code_id');
            $table->dropColumn(['promo_code', 'discount_total']);
        });

        Schema::dropIfExists('promo_codes');
    }
};
