<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for A/B testing products.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'product_ab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Test configuration
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            
            // Variants
            $table->foreignId('variant_a_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->nullOnDelete();
            $table->foreignId('variant_b_id')
                ->nullable()
                ->constrained($this->prefix.'products')
                ->nullOnDelete();
            
            // Test type
            $table->enum('test_type', [
                'title',
                'description',
                'image',
                'price',
                'layout',
                'cta',
                'custom',
            ])->index();
            
            // Test parameters (JSON)
            $table->json('variant_a_config')->nullable();
            $table->json('variant_b_config')->nullable();
            
            // Traffic split
            $table->integer('traffic_split_a')->default(50); // Percentage
            $table->integer('traffic_split_b')->default(50);
            
            // Status
            $table->enum('status', ['draft', 'running', 'paused', 'completed', 'cancelled'])
                ->default('draft')
                ->index();
            
            // Dates
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            
            // Results
            $table->integer('visitors_a')->default(0);
            $table->integer('visitors_b')->default(0);
            $table->integer('conversions_a')->default(0);
            $table->integer('conversions_b')->default(0);
            $table->decimal('conversion_rate_a', 5, 4)->default(0);
            $table->decimal('conversion_rate_b', 5, 4)->default(0);
            $table->decimal('revenue_a', 12, 2)->default(0);
            $table->decimal('revenue_b', 12, 2)->default(0);
            
            // Statistical significance
            $table->decimal('confidence_level', 5, 2)->nullable();
            $table->enum('winner', ['a', 'b', 'none', 'inconclusive'])->nullable();
            
            // Minimum sample size
            $table->integer('min_sample_size')->default(1000);
            $table->integer('min_duration_days')->default(7);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'status']);
            $table->index(['status', 'started_at']);
            $table->index(['test_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_ab_tests');
    }
};

