<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for advanced variant features:
     * - Serial number tracking
     * - Expiry dates
     * - Lot/batch tracking
     * - Subscription variants
     * - Digital-only variants
     * - License key management
     * - Variant personalization fields
     */
    public function up(): void
    {
        // Add advanced fields to product_variants table
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Expiry dates
            $table->date('expiry_date')->nullable()->after('preorder_release_date');
            $table->integer('shelf_life_days')->nullable()->after('expiry_date'); // Days until expiry
            
            // Subscription
            $table->boolean('is_subscription')->default(false)->after('shelf_life_days');
            $table->string('subscription_interval')->nullable()->after('is_subscription'); // daily, weekly, monthly, yearly
            $table->integer('subscription_interval_count')->default(1)->after('subscription_interval');
            $table->integer('subscription_trial_days')->nullable()->after('subscription_interval_count');
            
            // Digital-only
            $table->boolean('is_digital')->default(false)->after('subscription_trial_days');
            $table->boolean('requires_license_key')->default(false)->after('is_digital');
            
            // Lot/batch tracking
            $table->boolean('requires_lot_tracking')->default(false)->after('requires_license_key');
            
            // Personalization
            $table->boolean('allows_personalization')->default(false)->after('requires_lot_tracking');
            $table->json('personalization_fields')->nullable()->after('allows_personalization'); // Field definitions
        });

        // Serial numbers table
        Schema::create($this->prefix.'variant_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('order_line_id')
                ->nullable()
                ->constrained($this->prefix.'order_lines')
                ->onDelete('set null');
            $table->string('serial_number')->unique()->index();
            $table->enum('status', ['available', 'allocated', 'sold', 'returned', 'damaged'])->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status']);
        });

        // Lot/batch tracking table
        Schema::create($this->prefix.'variant_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->string('lot_number')->index();
            $table->string('batch_number')->nullable()->index();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('quantity_allocated')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional batch data
            $table->timestamps();

            $table->unique(['product_variant_id', 'lot_number']);
            $table->index(['product_variant_id', 'expiry_date']);
        });

        // License keys table
        Schema::create($this->prefix.'variant_license_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('order_line_id')
                ->nullable()
                ->constrained($this->prefix.'order_lines')
                ->onDelete('set null');
            $table->string('license_key')->unique()->index();
            $table->enum('status', ['available', 'allocated', 'activated', 'expired', 'revoked'])->default('available')->index();
            $table->date('expiry_date')->nullable();
            $table->integer('max_activations')->default(1);
            $table->integer('current_activations')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status']);
            $table->index(['license_key', 'status']);
        });

        // License activations table
        Schema::create($this->prefix.'license_key_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_key_id')
                ->constrained($this->prefix.'variant_license_keys')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('activated_at')->index();
            $table->timestamp('deactivated_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['license_key_id', 'is_active']);
        });

        // Variant personalizations table
        Schema::create($this->prefix.'variant_personalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')
                ->constrained($this->prefix.'product_variants')
                ->onDelete('cascade');
            $table->foreignId('order_line_id')
                ->nullable()
                ->constrained($this->prefix.'order_lines')
                ->onDelete('set null');
            $table->string('field_name'); // e.g., 'engraving_text', 'custom_text', 'upload_file'
            $table->string('field_type'); // text, textarea, file, image, select
            $table->text('field_value')->nullable(); // Text value or file path
            $table->json('field_options')->nullable(); // Options for select fields
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'order_line_id']);
            $table->index(['field_name', 'field_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'variant_personalizations');
        Schema::dropIfExists($this->prefix.'license_key_activations');
        Schema::dropIfExists($this->prefix.'variant_license_keys');
        Schema::dropIfExists($this->prefix.'variant_lots');
        Schema::dropIfExists($this->prefix.'variant_serial_numbers');

        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'expiry_date',
                'shelf_life_days',
                'is_subscription',
                'subscription_interval',
                'subscription_interval_count',
                'subscription_trial_days',
                'is_digital',
                'requires_license_key',
                'requires_lot_tracking',
                'allows_personalization',
                'personalization_fields',
            ]);
        });
    }
};


