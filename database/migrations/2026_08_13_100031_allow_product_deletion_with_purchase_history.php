<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete();
        });
    }
};
