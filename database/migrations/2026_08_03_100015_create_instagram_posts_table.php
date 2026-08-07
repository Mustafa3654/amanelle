<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Curated Instagram posts.
     *
     * Meta retired the Basic Display API, so pulling a public grid now needs a
     * Business account, a linked Facebook Page and a long-lived token that
     * expires. This table is the version that always works: paste the post
     * URL, upload the still. The Graph API can populate the same rows later
     * without the storefront changing.
     */
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('permalink');
            $table->string('image_path')->nullable();
            $table->json('caption')->nullable();

            // Their content is mostly Reels, shot 9:16. Squares would crop the
            // faces and products out of frame.
            $table->boolean('is_video')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
