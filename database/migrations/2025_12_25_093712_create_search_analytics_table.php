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
        Schema::create($this->prefix.'search_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('search_term', 255)->index();
            $table->unsignedInteger('result_count')->default(0);
            $table->boolean('zero_results')->default(false)->index();
            $table->unsignedBigInteger('clicked_product_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('filters')->nullable(); // Store applied filters
            $table->string('session_id')->nullable()->index();
            $table->timestamp('searched_at')->useCurrent()->index();
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['search_term', 'searched_at']);
            $table->index(['zero_results', 'searched_at']);
            $table->index(['clicked_product_id', 'searched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'search_analytics');
    }
};
