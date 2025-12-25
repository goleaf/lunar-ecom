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
        Schema::create($this->prefix.'download_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('download_link_id')->constrained($this->prefix.'download_links')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained($this->prefix.'customers')->onDelete('set null');
            
            // Download details
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            
            $table->timestamp('downloaded_at');
            
            // Indexes
            $table->index(['download_link_id', 'downloaded_at']);
            $table->index('downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'download_logs');
    }
};

