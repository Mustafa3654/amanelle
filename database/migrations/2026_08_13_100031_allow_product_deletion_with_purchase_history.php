<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a product be deleted without erasing what was purchased.
     *
     * The line keeps its snapshot of what was bought and simply loses the
     * link, rather than blocking the delete or cascading the history away.
     *
     * Both the information_schema lookup and `ALTER TABLE ... MODIFY` are
     * MySQL-only. The test suite runs on SQLite, which has neither, and an
     * unguarded call there kills the whole migration chain and every
     * database-touching test with it.
     *
     * SQLite needs no work: it cannot alter a column in place, and Laravel
     * builds these tables without enforcing foreign keys unless asked, so the
     * nullable column already behaves the way this migration is arranging.
     */
    public function up(): void
    {
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $hasConstraint = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'purchase_invoice_items')
            ->where('CONSTRAINT_NAME', 'purchase_invoice_items_product_variant_id_foreign')
            ->exists();

        if ($hasConstraint) {
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
        if (! in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('purchase_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete();
        });
    }
};
