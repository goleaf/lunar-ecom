<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_sales_reps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Sales rep user
            $table->boolean('is_primary')->default(false)->index();
            $table->decimal('commission_rate', 5, 2)->nullable(); // Commission percentage
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['contract_id', 'is_primary']);
            $table->index(['user_id']);
            $table->unique(['contract_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_sales_reps');
    }
};


