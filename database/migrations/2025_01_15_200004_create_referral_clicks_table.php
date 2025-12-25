<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('referral_code')->index();
            $table->string('ip_hash'); // Hashed IP for privacy
            $table->string('user_agent_hash'); // Hashed user agent
            $table->string('landing_url')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['referrer_user_id', 'created_at']);
            $table->index(['referral_code', 'created_at']);
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_clicks');
    }
};

