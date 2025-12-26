<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_hash', 64)->unique();
            $table->string('user_agent_hash', 64)->nullable();
            $table->string('screen_resolution')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('fingerprint_hash');
            $table->index('user_agent_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_fingerprints');
    }
};


