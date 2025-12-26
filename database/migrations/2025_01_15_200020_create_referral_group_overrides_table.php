<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_group_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_group_id')->constrained('user_groups')->onDelete('cascade');
            $table->foreignId('referral_program_id')->nullable()->constrained('referral_programs')->onDelete('cascade');
            $table->foreignId('referral_rule_id')->nullable()->constrained('referral_rules')->onDelete('cascade');
            $table->decimal('reward_value_override', 10, 2)->nullable();
            $table->string('stacking_mode_override')->nullable();
            $table->integer('max_redemptions_override')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('auto_vip_tiers')->nullable(); // {"referral_count": {"5": "VIP", "10": "Premium"}}
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_group_id', 'referral_program_id', 'referral_rule_id']);
            $table->index(['user_group_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_group_overrides');
    }
};


