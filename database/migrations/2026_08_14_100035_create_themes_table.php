<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('effect')->default('none');
            $table->string('surface')->default('#faf7f2');
            $table->string('surface_2')->default('#f2ece1');
            $table->string('accent')->default('#8c6a3a');
            $table->string('accent_fill')->default('#c9a96e');
            $table->string('accent_soft')->default('#e8d5a3');
            $table->string('ink')->default('#1a1612');
            $table->string('banner_image')->nullable();
            $table->string('greeting')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
