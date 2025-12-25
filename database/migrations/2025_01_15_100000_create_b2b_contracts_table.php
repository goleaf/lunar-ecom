<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'b2b_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id')->unique()->index(); // External contract reference
            $table->foreignId('customer_id')->constrained($this->prefix.'customers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained($this->prefix.'currencies')->onDelete('set null');
            $table->integer('priority')->default(0)->index(); // Higher priority = applied first
            $table->enum('status', ['draft', 'pending_approval', 'active', 'expired', 'cancelled'])->default('draft')->index();
            $table->enum('approval_state', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('terms_reference')->nullable(); // Reference to terms document
            $table->json('meta')->nullable(); // Additional contract metadata
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['customer_id', 'status', 'valid_from', 'valid_to']);
            $table->index(['status', 'approval_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'b2b_contracts');
    }
};

