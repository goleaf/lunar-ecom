<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_landing_templates', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->enum('status', ['draft', 'active'])->default('draft');
            $table->boolean('is_default')->default(false);

            // Locales supported by this template (e.g. ["en","ru","de"])
            $table->json('supported_locales')->nullable();

            // Per-locale content blocks and fields.
            // Shape:
            // {
            //   "en": { "page_title": "...", "benefits": ["..."], "faq": [{"q":"...","a":"..."}], ... },
            //   "ru": { ... }
            // }
            $table->json('content')->nullable();

            // SEO toggles
            $table->boolean('noindex')->default(true);
            $table->string('og_image_url')->nullable();

            // Cache version key (increment on update)
            $table->unsignedInteger('version')->default(1);

            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_landing_templates');
    }
};


