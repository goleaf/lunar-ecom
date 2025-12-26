<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances variant analytics tables with:
     * - Views per variant
     * - Conversion rate
     * - Revenue per variant
     * - Stock turnover
     * - Return rate per variant
     * - Discount impact
     * - Variant popularity ranking
     */
    public function up(): void
    {
        // Enhance variant_performance table if it exists
        if (Schema::hasTable($this->prefix.'variant_performance')) {
            Schema::table($this->prefix.'variant_performance', function (Blueprint $table) {
                // Return rate
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'returns_count')) {
                    $table->integer('returns_count')->default(0)->after('quantity_sold');
                }
                
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'return_rate')) {
                    $table->decimal('return_rate', 5, 4)->default(0)->after('returns_count');
                }
                
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'return_revenue')) {
                    $table->integer('return_revenue')->default(0)->after('return_rate');
                }
                
                // Discount impact
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'discount_applied_count')) {
                    $table->integer('discount_applied_count')->default(0)->after('return_revenue');
                }
                
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'discount_amount_total')) {
                    $table->integer('discount_amount_total')->default(0)->after('discount_applied_count');
                }
                
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'discount_impact_revenue')) {
                    $table->integer('discount_impact_revenue')->default(0)->after('discount_amount_total');
                }
                
                // Popularity ranking
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'popularity_score')) {
                    $table->decimal('popularity_score', 10, 2)->default(0)->index()->after('discount_impact_revenue');
                }
                
                if (!Schema::hasColumn($this->prefix.'variant_performance', 'popularity_rank')) {
                    $table->integer('popularity_rank')->nullable()->index()->after('popularity_score');
                }
            });
        }

        // Create variant_views table for tracking views
        if (!Schema::hasTable($this->prefix.'variant_views')) {
            Schema::create($this->prefix.'variant_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variant_id')
                    ->constrained($this->prefix.'product_variants')
                    ->onDelete('cascade');
                $table->string('session_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('channel_id')->nullable()->constrained($this->prefix.'channels')->nullOnDelete();
                $table->string('referrer')->nullable();
                $table->timestamp('viewed_at')->index();
                $table->timestamps();

                $table->index(['product_variant_id', 'viewed_at']);
                $table->index(['product_variant_id', 'session_id']);
            });
        }

        // Create variant_returns table for tracking returns
        if (!Schema::hasTable($this->prefix.'variant_returns')) {
            Schema::create($this->prefix.'variant_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variant_id')
                    ->constrained($this->prefix.'product_variants')
                    ->onDelete('cascade');
                $table->foreignId('order_id')
                    ->constrained($this->prefix.'orders')
                    ->onDelete('cascade');
                $table->foreignId('order_line_id')
                    ->constrained($this->prefix.'order_lines')
                    ->onDelete('cascade');
                $table->integer('quantity_returned')->default(1);
                $table->integer('refund_amount')->default(0); // Refund amount in cents
                $table->enum('return_reason', [
                    'defective',
                    'wrong_item',
                    'not_as_described',
                    'changed_mind',
                    'damaged',
                    'other',
                ])->nullable();
                $table->text('return_notes')->nullable();
                $table->enum('status', [
                    'pending',
                    'approved',
                    'rejected',
                    'refunded',
                    'completed',
                ])->default('pending')->index();
                $table->timestamp('returned_at')->index();
                $table->timestamps();

                $table->index(['product_variant_id', 'returned_at']);
                $table->index(['product_variant_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_returns');
        Schema::dropIfExists($this->prefix.'variant_views');

        if (Schema::hasTable($this->prefix.'variant_performance')) {
            Schema::table($this->prefix.'variant_performance', function (Blueprint $table) {
                $columns = [
                    'returns_count',
                    'return_rate',
                    'return_revenue',
                    'discount_applied_count',
                    'discount_amount_total',
                    'discount_impact_revenue',
                    'popularity_score',
                    'popularity_rank',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn($this->prefix.'variant_performance', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};


