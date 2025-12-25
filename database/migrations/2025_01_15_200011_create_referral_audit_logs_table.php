<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('actor_type', ['admin', 'user', 'system'])->default('system');
            $table->foreignId('actor_id')->nullable(); // Polymorphic: user_id or admin_id
            $table->string('action'); // rule_updated, reward_issued, reward_reversed, attribution_changed, etc.
            $table->morphs('subject'); // subject_type, subject_id (polymorphic)
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['actor_type', 'actor_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_audit_logs');
    }
};

