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
        Schema::create($this->prefix.'review_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained($this->prefix.'reviews')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            
            // Vote type: true = helpful, false = not helpful
            $table->boolean('is_helpful')->default(true)->index();
            
            $table->timestamps();
            
            // Prevent duplicate votes (spam prevention)
            $table->unique(['review_id', 'customer_id'], 'unique_customer_vote');
            $table->unique(['review_id', 'session_id'], 'unique_session_vote');
            $table->unique(['review_id', 'ip_address'], 'unique_ip_vote');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'review_helpful_votes');
    }
};
