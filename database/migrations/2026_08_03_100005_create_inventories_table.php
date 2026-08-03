<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();

            // Stock is per market, not global. Amanelle sources from Saudi and
            // sells into Lebanon, so the same shade can be in stock in one
            // market and out in the other.
            $table->string('market', 2)->default('SA');

            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->unique(['product_variant_id', 'market']);
            $table->index(['market', 'quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
