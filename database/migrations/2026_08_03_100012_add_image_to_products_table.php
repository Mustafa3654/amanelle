<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // The main shot. Variants keep their own image_path as an
            // override, so a lipstick can show each shade while a perfume
            // needs only one photo for every size.
            $table->string('image_path')->nullable()->after('slug');

            // Extra angles, packaging, the QR authenticity sticker. JSON
            // rather than a table: they are an ordered list belonging to one
            // product and are never queried individually.
            $table->json('gallery')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'gallery']);
        });
    }
};
