<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates table for reusable attribute groups:
     * - Reusable attribute groups
     * - Group ordering
     */
    public function up(): void
    {
        Schema::create($this->prefix.'attribute_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique();
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            
            // Settings
            $table->boolean('is_reusable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['is_reusable', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'attribute_groups');
    }
};


