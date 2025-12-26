<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['B2C', 'B2B', 'VIP', 'Staff', 'Partner', 'Other'])->default('B2C');
            $table->string('default_discount_stack_policy')->nullable(); // e.g., 'best_of', 'cumulative', 'exclusive'
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_groups');
    }
};


