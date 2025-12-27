<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consent_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('consent_type'); // 'cookie', 'marketing', 'analytics', 'data_processing', 'third_party'
            $table->string('purpose'); // Description of what the consent is for
            $table->boolean('consented')->default(false);
            $table->string('consent_method')->nullable(); // 'banner', 'settings', 'api', 'import'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'consent_type', 'consented']);
            $table->index(['customer_id', 'consent_type', 'consented']);
            $table->index(['session_id', 'consent_type']);
            $table->index('consented_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_tracking');
    }
};
