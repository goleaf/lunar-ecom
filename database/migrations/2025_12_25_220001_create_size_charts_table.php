<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration as LunarMigration;

return new class extends LunarMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'size_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('size_guide_id')->constrained($this->prefix.'size_guides')->cascadeOnDelete();
            
            // Size information
            $table->string('size_name')->index(); // XS, S, M, L, XL, XXL, etc.
            $table->string('size_code')->nullable()->index(); // Numeric or alphanumeric code
            $table->integer('size_order')->default(0)->index(); // For sorting
            
            // Measurements (JSON)
            // {
            //   "chest": 38,
            //   "waist": 32,
            //   "hips": 40,
            //   "length": 28,
            //   "shoulder": 16,
            //   "sleeve": 24,
            //   "inseam": 30,
            //   "neck": 15,
            //   "bust": 36,
            //   "cup": "B"
            // }
            $table->json('measurements');
            
            // Size range (for numeric sizes)
            $table->integer('size_min')->nullable();
            $table->integer('size_max')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['size_guide_id', 'size_order']);
            $table->unique(['size_guide_id', 'size_name'], 'size_guide_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'size_charts');
    }
};


