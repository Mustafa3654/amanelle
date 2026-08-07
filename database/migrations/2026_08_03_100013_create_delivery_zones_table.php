<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();

            // In the base currency (USD), converted for display like every
            // other price on the site.
            $table->decimal('fee', 10, 2)->default(0);

            // Above this subtotal the fee is waived. Null means never free.
            $table->decimal('free_above', 10, 2)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Snapshot the zone name alongside the id: renaming or deleting a
            // zone must not rewrite where a past order was sent.
            $table->foreignId('delivery_zone_id')->nullable()->after('city')
                ->constrained()->nullOnDelete();
            $table->string('delivery_zone_name')->nullable()->after('delivery_zone_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_zone_id');
            $table->dropColumn('delivery_zone_name');
        });

        Schema::dropIfExists('delivery_zones');
    }
};
