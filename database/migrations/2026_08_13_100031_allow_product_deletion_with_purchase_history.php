<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $constraint = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'purchase_invoice_items')
            ->where('CONSTRAINT_NAME', 'purchase_invoice_items_product_variant_id_foreign')
            ->exists();

        if ($constraint) {
            Schema::table('purchase_invoice_items', function (Blueprint $table) {
                $table->dropForeign(['product_variant_id']);
            });
        }

        DB::statement('ALTER TABLE purchase_invoice_items MODIFY product_variant_id BIGINT UNSIGNED NULL');

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
