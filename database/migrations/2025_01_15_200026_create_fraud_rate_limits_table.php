<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('identifier_type'); // 'ip', 'device_fingerprint', 'email', 'payment_fingerprint'
            $table->string('identifier_hash', 64);
            $table->string('action_type'); // 'signup', 'order', 'referral_click'
            $table->integer('count')->default(0);
            $table->date('date');
            $table->timestamps();

            $table->unique(['identifier_type', 'identifier_hash', 'action_type', 'date']);
            $table->index(['identifier_type', 'identifier_hash', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_rate_limits');
    }
};


