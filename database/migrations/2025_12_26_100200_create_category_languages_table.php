<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'category_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained($this->prefix.'categories')->onDelete('cascade');
            $table->foreignId('language_id')->constrained($this->prefix.'languages')->onDelete('cascade');
            $table->boolean('is_visible')->default(true)->index();
            $table->boolean('is_in_navigation')->default(true)->index();
            $table->timestamps();
            
            $table->unique(['category_id', 'language_id']);
            $table->index(['language_id', 'is_visible']);
            $table->index(['language_id', 'is_in_navigation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'category_languages');
    }
};

