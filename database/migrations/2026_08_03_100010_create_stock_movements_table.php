<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An append-only log of every change to stock.
     *
     * Without it, "why does the system say 3 and the shelf says 2?" is
     * unanswerable. With it, every unit is traceable to the order or manual
     * adjustment that moved it.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('market', 2)->default('LB');

            $table->enum('type', [
                'reserve',   // order placed — reserved up, quantity untouched
                'release',   // order cancelled or expired — reserved down
                'fulfil',    // order delivered — quantity and reserved both down
                'adjust',    // manual correction, stocktake, damage
            ]);

            // Signed deltas, so replaying the log from zero reproduces the
            // current row exactly.
            $table->integer('quantity_delta')->default(0);
            $table->integer('reserved_delta')->default(0);

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['product_variant_id', 'market']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
