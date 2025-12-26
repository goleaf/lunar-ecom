<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'contract_company_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_customer_id')->constrained($this->prefix.'customers')->onDelete('cascade');
            $table->foreignId('child_customer_id')->constrained($this->prefix.'customers')->onDelete('cascade');
            $table->string('relationship_type')->default('subsidiary')->index(); // 'subsidiary', 'division', 'branch'
            $table->boolean('inherit_contracts')->default(false); // Whether child inherits parent contracts
            $table->json('meta')->nullable();
            $table->timestamps();

            // Prevent self-referencing and duplicates
            $table->unique(['parent_customer_id', 'child_customer_id']);
            $table->index(['parent_customer_id']);
            $table->index(['child_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'contract_company_hierarchies');
    }
};


