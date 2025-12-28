<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->disableForeignKeyConstraints();
        try {
            // Drop both prefixed + unprefixed variants to support older installs.
            foreach ([
                'comparison_analytics',
                'product_comparisons',
                'lunar_comparison_analytics',
                'lunar_product_comparisons',
            ] as $table) {
                if ($schema->hasTable($table)) {
                    $schema->drop($table);
                }
            }
        } finally {
            $schema->enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Recreate tables (unprefixed) for rollback safety.
        Schema::connection($this->getConnection())->create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id')->nullable()->index();

            $table->json('product_ids');
            $table->json('selected_attributes')->nullable();

            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['user_id']);
            $table->unique(['session_id']);
        });

        Schema::connection($this->getConnection())->create('comparison_analytics', function (Blueprint $table) {
            $table->id();

            $table->json('product_ids');
            $table->unsignedInteger('comparison_count')->default(1)->index();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('session_id')->nullable()->index();

            $table->timestamp('compared_at')->index();
            $table->timestamps();

            $table->index(['product_ids', 'compared_at']);
        });
    }
};

