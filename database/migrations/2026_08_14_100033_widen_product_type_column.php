<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turns products.type from an ENUM into a plain string.
     *
     * As an ENUM, adding a product type meant a schema change — the database
     * would reject anything outside the original three, so "we also sell hair
     * care now" became a migration. The allowed values live in
     * config/amanelle.php instead, where the admin form reads them, and the
     * column just stores whatever was chosen.
     *
     * Raw SQL because Laravel's ->change() needs doctrine/dbal to alter an
     * ENUM, and MySQL is the only driver that had one in the first place —
     * SQLite stores enums as text, so it already behaves this way.
     */
    public function up(): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE products MODIFY type VARCHAR(32) NOT NULL DEFAULT 'fragrance'");
        }

        Schema::table('products', function (Blueprint $table) {
            $table->index('type', 'products_type_index_v2');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_type_index_v2');
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE products MODIFY type ENUM('fragrance', 'skincare', 'makeup') NOT NULL");
        }
    }
};
