<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->enum('status', ['active', 'banned'])->default('active')->after('phone');
            $table->foreignId('group_id')->nullable()->constrained('user_groups')->onDelete('set null')->after('status');
            $table->string('referral_code')->nullable()->unique()->after('group_id');
            $table->string('referral_link_slug')->nullable()->unique()->after('referral_code');
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->onDelete('set null')->after('referral_link_slug');
            $table->timestamp('referred_at')->nullable()->after('referred_by_user_id');
            $table->boolean('referral_blocked')->default(false)->after('referred_at');
            
            $table->index(['status', 'group_id']);
            $table->index('referral_code');
            $table->index('referred_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['referred_by_user_id']);
            $table->dropColumn([
                'phone',
                'status',
                'group_id',
                'referral_code',
                'referral_link_slug',
                'referred_by_user_id',
                'referred_at',
                'referral_blocked',
            ]);
        });
    }
};

