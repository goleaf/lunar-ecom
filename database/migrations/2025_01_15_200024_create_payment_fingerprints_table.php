<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_hash', 64)->unique();
            $table->string('card_last4', 4)->nullable();
            $table->string('card_brand')->nullable();
            $table->string('card_country')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('fingerprint_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_fingerprints');
    }
};


