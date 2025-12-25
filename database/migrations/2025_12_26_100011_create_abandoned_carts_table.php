<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for tracking abandoned carts.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')
                ->nullable()
                ->constrained($this->prefix.'carts')
                ->nullOnDelete();
            $table->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->foreignId('variant_id')
                ->nullable()
                ->constrained($this->prefix.'product_variants')
                ->nullOnDelete();
            
            // User/Session
            $table->foreignId('user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            
            // Cart details
            $table->integer('quantity')->default(1);
            $table->bigInteger('price')->default(0);
            $table->bigInteger('total')->default(0);
            
            // Timestamps
            $table->timestamp('abandoned_at')->index();
            $table->timestamp('recovered_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            
            // Recovery attempts
            $table->integer('recovery_emails_sent')->default(0);
            $table->timestamp('last_recovery_email_at')->nullable();
            
            // Status
            $table->enum('status', ['abandoned', 'recovered', 'converted', 'expired'])
                ->default('abandoned')
                ->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'abandoned_at']);
            $table->index(['status', 'abandoned_at']);
            $table->index(['user_id', 'abandoned_at']);
            $table->index(['session_id', 'abandoned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'abandoned_carts');
    }
};

