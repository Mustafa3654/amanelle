<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('default_cost_price', 10, 2)->nullable()->after('search_text');
            $table->decimal('default_sale_price', 10, 2)->nullable()->after('default_cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['default_cost_price', 'default_sale_price']);
        });
    }
};
