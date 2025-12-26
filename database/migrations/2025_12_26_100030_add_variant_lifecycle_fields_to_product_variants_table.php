<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds lifecycle and workflow fields:
     * - Draft status support
     * - Approval workflow
     * - Scheduled activation/deactivation
     * - Lock variants with active orders
     */
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            // Update status enum to include 'draft'
            // Note: This requires dropping and recreating the column
            if (Schema::hasColumn($this->prefix.'product_variants', 'status')) {
                // For MySQL/MariaDB, we need to modify the enum
                $table->enum('status', ['draft', 'active', 'inactive', 'archived'])
                    ->default('draft')
                    ->change();
            } else {
                $table->enum('status', ['draft', 'active', 'inactive', 'archived'])
                    ->default('draft')
                    ->index()
                    ->after('visibility');
            }
            
            // Approval workflow
            if (!Schema::hasColumn($this->prefix.'product_variants', 'approval_status')) {
                $table->enum('approval_status', [
                    'pending',
                    'approved',
                    'rejected',
                    'not_required'
                ])->default('not_required')->index()->after('status');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('approval_status');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
            
            // Scheduled activation/deactivation
            if (!Schema::hasColumn($this->prefix.'product_variants', 'scheduled_activation_at')) {
                $table->dateTime('scheduled_activation_at')->nullable()->index()->after('rejection_reason');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'scheduled_deactivation_at')) {
                $table->dateTime('scheduled_deactivation_at')->nullable()->index()->after('scheduled_activation_at');
            }
            
            // Lock variants with active orders
            if (!Schema::hasColumn($this->prefix.'product_variants', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->index()->after('scheduled_deactivation_at');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'locked_reason')) {
                $table->string('locked_reason', 255)->nullable()->after('is_locked');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'locked_at')) {
                $table->dateTime('locked_at')->nullable()->after('locked_reason');
            }
            
            // Clone source tracking
            if (!Schema::hasColumn($this->prefix.'product_variants', 'cloned_from_id')) {
                $table->foreignId('cloned_from_id')
                    ->nullable()
                    ->constrained($this->prefix.'product_variants')
                    ->nullOnDelete()
                    ->after('locked_at');
            }
            
            if (!Schema::hasColumn($this->prefix.'product_variants', 'cloned_at')) {
                $table->dateTime('cloned_at')->nullable()->after('cloned_from_id');
            }
            
            // Indexes
            $table->index(['status', 'approval_status', 'is_locked']);
            $table->index(['scheduled_activation_at', 'scheduled_deactivation_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $columns = [
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
                'scheduled_activation_at',
                'scheduled_deactivation_at',
                'is_locked',
                'locked_reason',
                'locked_at',
                'cloned_from_id',
                'cloned_at',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn($this->prefix.'product_variants', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Revert status enum (if needed)
            if (Schema::hasColumn($this->prefix.'product_variants', 'status')) {
                $table->enum('status', ['active', 'inactive', 'archived'])
                    ->default('active')
                    ->change();
            }
        });
    }
};


