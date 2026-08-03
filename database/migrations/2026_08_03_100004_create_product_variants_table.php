<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();

            // One variant table, three axes. A fragrance fills volume_ml and
            // concentration; a lipstick fills shade_name and shade_hex; a
            // serum fills volume_ml alone. Unused axes stay null rather than
            // forcing three near-identical tables.
            $table->unsignedSmallInteger('volume_ml')->nullable();
            $table->enum('concentration', ['edc', 'edt', 'edp', 'parfum', 'extrait', 'mist', 'oil'])->nullable();
            $table->json('shade_name')->nullable();
            $table->string('shade_hex', 7)->nullable();

            $table->decimal('price', 10, 2);
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('SAR');

            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
