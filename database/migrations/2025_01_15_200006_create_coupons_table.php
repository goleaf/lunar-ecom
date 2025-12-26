<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed', 'free_shipping'])->default('percentage');
            $table->decimal('value', 15, 2);
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('per_user_limit')->default(1); // Per user limit
            $table->json('eligible_product_ids')->nullable();
            $table->json('eligible_category_ids')->nullable();
            $table->json('eligible_collection_ids')->nullable();
            $table->string('stack_policy')->nullable(); // e.g., 'exclusive', 'stackable', 'best_of'
            $table->foreignId('created_by_rule_id')->nullable()->constrained('referral_rules')->onDelete('set null');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['code', 'is_active']);
            $table->index(['assigned_to_user_id', 'is_active']);
            $table->index(['created_by_rule_id']);
            $table->index(['start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};


