<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->foreignId('price_list_id')->nullable()->constrained($this->prefix.'price_lists')->onDelete('set null');
            $table->foreignId('contract_price_id')->nullable()->constrained($this->prefix.'contract_prices')->onDelete('set null');
            $table->string('audit_type')->index(); // 'price_change', 'usage', 'margin_analysis', 'expiry_alert'
            $table->string('action'); // 'created', 'updated', 'deleted', 'price_changed', 'used', etc.
            $table->text('description')->nullable();
            $table->json('old_values')->nullable(); // Previous values (for price changes)
            $table->json('new_values')->nullable(); // New values
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Who made the change
            $table->foreignId('order_id')->nullable()->constrained($this->prefix.'orders')->onDelete('set null'); // Related order (for usage tracking)
            $table->decimal('margin_percentage', 5, 2)->nullable(); // For margin analysis
            $table->integer('quantity')->nullable(); // For usage tracking
            $table->integer('total_value')->nullable(); // For usage tracking
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['contract_id', 'audit_type', 'created_at']);
            $table->index(['price_list_id']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_audits');
    }
};

