<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained($this->prefix.'b2b_contracts')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained($this->prefix.'price_lists')->onDelete('cascade'); // For inheritance
            $table->string('version')->default('1.0')->index(); // Versioning support
            $table->boolean('is_active')->default(true)->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('priority')->default(0)->index(); // Higher priority = applied first
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['contract_id', 'is_active', 'valid_from', 'valid_to']);
            $table->index(['parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'price_lists');
    }
};


