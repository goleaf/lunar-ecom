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
        Schema::create($this->prefix.'seasonal_product_rules', function (Blueprint $table) {
            $table->id();
            $table->string('season_tag')->index()->comment('e.g., "christmas", "summer", "black_friday"');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('timezone')->default('UTC');
            $table->boolean('auto_publish')->default(true);
            $table->boolean('auto_unpublish')->default(true);
            $table->integer('days_before_start')->default(0)->comment('Days before season to publish');
            $table->integer('days_after_end')->default(0)->comment('Days after season to unpublish');
            $table->json('applied_to_products')->nullable()->comment('Specific product IDs');
            $table->json('applied_to_categories')->nullable()->comment('Category IDs');
            $table->json('applied_to_tags')->nullable()->comment('Tag IDs');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            
            $table->index(['season_tag', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'seasonal_product_rules');
    }
};


