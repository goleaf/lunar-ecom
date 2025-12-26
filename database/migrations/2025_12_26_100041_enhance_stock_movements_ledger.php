<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances stock_movements table with comprehensive ledger fields:
     * - All movement types (Sale, Return, Manual adjustment, Import, Damage, Transfer, Correction)
     * - Actor tracking (created_by)
     * - Timestamp (movement_date)
     * - Before/after quantities (already exists)
     * - Reason field (already exists)
     */
    public function up(): void
    {
        Schema::table($this->prefix.'stock_movements', function (Blueprint $table) {
            // Enhance type enum if needed
            // Note: Laravel doesn't support modifying enum columns directly
            // You may need to drop and recreate if adding new types
            
            // Ensure actor field exists
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('notes');
            }
            
            // Add actor type (user, system, api, import)
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'actor_type')) {
                $table->string('actor_type')->default('user')->after('created_by')->index();
            }
            
            // Add actor identifier (for system/API actors)
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'actor_identifier')) {
                $table->string('actor_identifier')->nullable()->after('actor_type');
            }
            
            // Add IP address for audit trail
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('actor_identifier');
            }
            
            // Add metadata for additional context
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'metadata')) {
                $table->json('metadata')->nullable()->after('notes');
            }
            
            // Add reserved quantity tracking
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'reserved_quantity_before')) {
                $table->integer('reserved_quantity_before')->default(0)->after('quantity_after');
            }
            
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'reserved_quantity_after')) {
                $table->integer('reserved_quantity_after')->default(0)->after('reserved_quantity_before');
            }
            
            // Add available quantity tracking
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'available_quantity_before')) {
                $table->integer('available_quantity_before')->default(0)->after('reserved_quantity_after');
            }
            
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'available_quantity_after')) {
                $table->integer('available_quantity_after')->default(0)->after('available_quantity_before');
            }
            
            // Ensure movement_date has index
            if (!Schema::hasColumn($this->prefix.'stock_movements', 'movement_date')) {
                $table->timestamp('movement_date')->useCurrent()->index();
            }
            
            // Add index for actor tracking
            $table->index(['created_by', 'movement_date']);
            $table->index(['actor_type', 'movement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'stock_movements', function (Blueprint $table) {
            $columns = [
                'actor_type',
                'actor_identifier',
                'ip_address',
                'metadata',
                'reserved_quantity_before',
                'reserved_quantity_after',
                'available_quantity_before',
                'available_quantity_after',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'stock_movements', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            if (Schema::hasColumn($this->prefix.'stock_movements', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};


