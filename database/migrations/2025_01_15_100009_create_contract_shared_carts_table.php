<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_shared_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->foreignId('cart_id')->constrained($this->prefix.'carts')->onDelete('cascade');
            $table->string('name'); // Name for the shared cart
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // User who created the shared cart
            $table->json('shared_with')->nullable(); // Array of user IDs who can access this cart
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['contract_id', 'is_active']);
            $table->index(['cart_id']);
            $table->index(['created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_shared_carts');
    }
};

