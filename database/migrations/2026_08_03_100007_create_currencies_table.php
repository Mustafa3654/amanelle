<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->json('name');
            $table->string('symbol', 8);

            /*
             * Units of this currency per 1 unit of the base currency. USD is
             * the base and sits at 1.0; LBP holds something near 89,000.
             *
             * 18,6 rather than the usual money precision because the Lebanese
             * pound needs five and six significant digits before the decimal
             * even matters, and a 10,2 column would silently truncate it.
             */
            $table->decimal('rate', 18, 6)->default(1);

            // LBP is quoted in whole pounds; showing "89,431.00 ل.ل" is noise.
            $table->unsignedTinyInteger('decimals')->default(2);

            $table->boolean('is_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('rate_updated_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
