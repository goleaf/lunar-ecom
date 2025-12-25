<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'checkout_locks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('cart_id')->constrained($this->prefix.'carts')->onDelete('cascade');
            $table->string('session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('state')->index(); // pending, validating, reserving, locking_prices, authorizing, creating_order, capturing, committing, completed, failed
            $table->string('phase')->nullable(); // Current phase name
            $table->json('failure_reason')->nullable(); // Store failure details
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable(); // Store additional checkout metadata
            $table->timestamps();
            
            // Ensure one active checkout per cart
            $table->unique(['cart_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'checkout_locks');
    }
};

