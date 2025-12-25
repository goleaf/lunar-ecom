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
        Schema::create($this->prefix.'pricing_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_matrix_id')->constrained($this->prefix.'price_matrices')->onDelete('cascade');
            $table->foreignId('customer_group_id')->nullable()->constrained($this->prefix.'customer_groups')->onDelete('cascade');
            $table->string('status')->default('pending')->index(); // pending, approved, rejected
            $table->text('requested_changes')->nullable(); // JSON of requested pricing changes
            $table->text('approval_notes')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'customer_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'pricing_approvals');
    }
};
