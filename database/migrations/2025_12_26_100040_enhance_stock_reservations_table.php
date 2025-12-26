<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances stock_reservations table with:
     * - Reservation status (cart, order_confirmed, manual)
     * - Partial reservation support
     * - Reservation lock token for race-condition safety
     * - Reservation metadata
     */
    public function up(): void
    {
        Schema::table($this->prefix.'stock_reservations', function (Blueprint $table) {
            // Reservation status
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'status')) {
                $table->enum('status', [
                    'cart',              // Cart-based reservation
                    'order_confirmed',   // Order-confirmed reservation
                    'manual',            // Manual reservation override
                    'expired',           // Expired reservation
                    'released',          // Released reservation
                ])->default('cart')->after('quantity')->index();
            }
            
            // Partial reservation support
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'reserved_quantity')) {
                $table->integer('reserved_quantity')->default(0)->after('quantity')->index();
                // reserved_quantity <= quantity (partial fulfillment)
            }
            
            // Race-condition safe locking
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'lock_token')) {
                $table->string('lock_token', 64)->nullable()->unique()->after('session_id')->index();
            }
            
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('lock_token');
            }
            
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'lock_expires_at')) {
                $table->timestamp('lock_expires_at')->nullable()->after('locked_at')->index();
            }
            
            // Reservation metadata
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'metadata')) {
                $table->json('metadata')->nullable()->after('released_at');
            }
            
            // Confirmation tracking
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('released_at');
            }
            
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete()->after('confirmed_at');
            }
            
            // Manual override tracking
            if (!Schema::hasColumn($this->prefix.'stock_reservations', 'override_reason')) {
                $table->text('override_reason')->nullable()->after('confirmed_by');
            }
            
            // Add index for status and expiration
            $table->index(['status', 'expires_at', 'is_released']);
            $table->index(['lock_token', 'lock_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'stock_reservations', function (Blueprint $table) {
            $columns = [
                'status',
                'reserved_quantity',
                'lock_token',
                'locked_at',
                'lock_expires_at',
                'metadata',
                'confirmed_at',
                'override_reason',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'stock_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            if (Schema::hasColumn($this->prefix.'stock_reservations', 'confirmed_by')) {
                $table->dropConstrainedForeignId('confirmed_by');
            }
        });
    }
};


