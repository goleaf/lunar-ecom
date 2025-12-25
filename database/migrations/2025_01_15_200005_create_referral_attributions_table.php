<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_attributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referee_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referrer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('program_id')->constrained('referral_programs')->onDelete('cascade');
            $table->string('code_used')->index();
            $table->timestamp('attributed_at');
            $table->enum('attribution_method', ['code', 'link', 'manual_admin'])->default('link');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['referee_user_id', 'status']);
            $table->index(['referrer_user_id', 'status']);
            $table->index(['program_id', 'status']);
            $table->index('attributed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_attributions');
    }
};

