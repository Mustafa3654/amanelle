<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('logo_path')->nullable();

            // Gulf houses (ASSAF, Gissah, Gulf Orchid, Match, Maison Alhambra)
            // and K-beauty (Some By Mi, Anua, Celimax, medicube) behave
            // differently in merchandising, so the origin is queryable.
            $table->string('origin_country', 2)->nullable();

            // Amanelle's whole pitch is authenticity against a market full of
            // counterfeits, so "we are an authorised stockist" is a claim the
            // catalogue has to be able to make per brand, not in prose.
            $table->boolean('is_authorised_stockist')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
