<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The "inspired by" mapping — Amanelle's actual conversion lever.
     *
     * The account already publishes these in the open ("Pink Marshmallow –
     * Gulf Orchid ⟶ بديل Kayali Yum", Match Bell shot beside La Vie Est
     * Belle). Buried in a description it is invisible to search; as a table a
     * customer can look for the designer scent they know and land on the
     * alternative, which is exactly the journey the captions describe.
     */
    public function up(): void
    {
        Schema::create('fragrance_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('designer_house');
            $table->string('original_name');

            // Powers "save X vs the original". Nullable because the reference
            // is still useful for discovery even when we have not verified a
            // current retail price to compare against.
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            $table->timestamps();

            $table->index(['designer_house', 'original_name']);
            $table->unique(['product_id', 'designer_house', 'original_name'], 'frag_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fragrance_references');
    }
};
