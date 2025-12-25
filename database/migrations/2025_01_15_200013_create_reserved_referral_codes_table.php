<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserved_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('reserved_for_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('reserved_for_email')->nullable(); // For future users
            $table->text('notes')->nullable();
            $table->foreignId('reserved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index('reserved_for_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_referral_codes');
    }
};

