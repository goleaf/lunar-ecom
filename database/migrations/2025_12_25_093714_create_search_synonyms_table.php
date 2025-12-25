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
        Schema::create($this->prefix.'search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term')->index();
            $table->json('synonyms'); // Array of synonym terms
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(0)->index(); // Higher priority = applied first
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique('term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'search_synonyms');
    }
};
