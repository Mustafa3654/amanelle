<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Drives which variant axes apply (see config/amanelle.php).
            // Fragrance dominates the catalogue, so it is a first-class type
            // rather than makeup with extra fields bolted on.
            $table->enum('type', ['fragrance', 'skincare', 'makeup'])->index();

            $table->json('name');
            $table->string('slug')->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();

            // Maintained by the model from every translation, so Arabic and
            // English both hit one index. A generated column over the JSON
            // would be neater, but MariaDB 10.4 (what XAMPP ships) is fussy
            // about indexing JSON extraction, and this stays portable.
            $table->text('search_text')->nullable()->fulltext();

            // --- Fragrance ---------------------------------------------------
            // Longevity and projection, not a note pyramid. Every caption on
            // @amanelle_beauty sells on "ثبات وفوحان" — that is what this
            // audience compares, so both are rated 1-5 and filterable.
            $table->unsignedTinyInteger('longevity')->nullable();
            $table->unsignedTinyInteger('projection')->nullable();
            $table->enum('gender', ['women', 'men', 'unisex'])->nullable();
            $table->json('notes_top')->nullable();
            $table->json('notes_heart')->nullable();
            $table->json('notes_base')->nullable();

            // --- Skincare ----------------------------------------------------
            $table->json('skin_types')->nullable();
            $table->json('concerns')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
