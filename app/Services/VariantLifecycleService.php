<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service for managing variant lifecycle and workflows.
 * 
 * Handles:
 * - Draft → Active → Archived flow
 * - Approval workflow
 * - Scheduled activation/deactivation
 * - Soft-delete with recovery
 * - Lock variants with active orders
 * - Clone variant
 * - Bulk enable / disable
 */
class VariantLifecycleService
{
    /**
     * Transition variant status.
     *
     * @param  ProductVariant  $variant
     * @param  string  $newStatus
     * @param  array  $options
     * @return bool
     */
    public function transitionStatus(ProductVariant $variant, string $newStatus, array $options = []): bool
    {
        $validStatuses = ['draft', 'active', 'inactive', 'archived'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$newStatus}");
        }

        // Check if variant is locked
        if ($variant->is_locked && !($options['force'] ?? false)) {
            throw new \RuntimeException("Variant is locked: {$variant->locked_reason}");
        }

        // Check approval if transitioning to active
        if ($newStatus === 'active' && $variant->approval_status === 'pending') {
            throw new \RuntimeException('Variant must be approved before activation');
        }

        return $variant->update([
            'status' => $newStatus,
            'updated_at' => now(),
        ]);
    }

    /**
     * Move variant from draft to active (with approval check).
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $approvedBy
     * @return bool
     */
    public function activate(ProductVariant $variant, ?int $approvedBy = null): bool
    {
        // Check approval if required
        if ($variant->approval_status === 'pending') {
            throw new \RuntimeException('Variant must be approved before activation');
        }

        // Check if locked
        if ($variant->is_locked) {
            throw new \RuntimeException("Variant is locked: {$variant->locked_reason}");
        }

        return $variant->update([
            'status' => 'active',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'approval_status' => 'approved',
        ]);
    }

    /**
     * Move variant to archived.
     *
     * @param  ProductVariant  $variant
     * @param  bool  $force
     * @return bool
     */
    public function archive(ProductVariant $variant, bool $force = false): bool
    {
        // Check if variant has active orders
        if (!$force && $this->hasActiveOrders($variant)) {
            throw new \RuntimeException('Cannot archive variant with active orders. Lock it instead.');
        }

        return $this->transitionStatus($variant, 'archived', ['force' => $force]);
    }

    /**
     * Submit variant for approval.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $submittedBy
     * @return bool
     */
    public function submitForApproval(ProductVariant $variant, ?int $submittedBy = null): bool
    {
        return $variant->update([
            'approval_status' => 'pending',
            'status' => 'draft', // Ensure status is draft when pending approval
        ]);
    }

    /**
     * Approve variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $approvedBy
     * @param  bool  $autoActivate
     * @return bool
     */
    public function approve(ProductVariant $variant, ?int $approvedBy = null, bool $autoActivate = false): bool
    {
        $updates = [
            'approval_status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ];

        if ($autoActivate && $variant->status === 'draft') {
            $updates['status'] = 'active';
        }

        return $variant->update($updates);
    }

    /**
     * Reject variant.
     *
     * @param  ProductVariant  $variant
     * @param  string  $reason
     * @param  int|null  $rejectedBy
     * @return bool
     */
    public function reject(ProductVariant $variant, string $reason, ?int $rejectedBy = null): bool
    {
        return $variant->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'status' => 'draft',
        ]);
    }

    /**
     * Schedule variant activation.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|string  $activationDate
     * @return bool
     */
    public function scheduleActivation(ProductVariant $variant, $activationDate): bool
    {
        $date = $activationDate instanceof Carbon ? $activationDate : Carbon::parse($activationDate);

        return $variant->update([
            'scheduled_activation_at' => $date,
            'status' => 'draft', // Ensure status is draft when scheduled
        ]);
    }

    /**
     * Schedule variant deactivation.
     *
     * @param  ProductVariant  $variant
     * @param  Carbon|string  $deactivationDate
     * @return bool
     */
    public function scheduleDeactivation(ProductVariant $variant, $deactivationDate): bool
    {
        $date = $deactivationDate instanceof Carbon ? $deactivationDate : Carbon::parse($deactivationDate);

        return $variant->update([
            'scheduled_deactivation_at' => $date,
        ]);
    }

    /**
     * Process scheduled activations.
     *
     * @return int Number of variants activated
     */
    public function processScheduledActivations(): int
    {
        $variants = ProductVariant::where('status', 'draft')
            ->where('scheduled_activation_at', '<=', now())
            ->whereNotNull('scheduled_activation_at')
            ->get();

        $activated = 0;

        foreach ($variants as $variant) {
            try {
                if ($this->activate($variant)) {
                    $variant->update(['scheduled_activation_at' => null]);
                    $activated++;
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                \Log::error("Failed to activate scheduled variant {$variant->id}: {$e->getMessage()}");
            }
        }

        return $activated;
    }

    /**
     * Process scheduled deactivations.
     *
     * @return int Number of variants deactivated
     */
    public function processScheduledDeactivations(): int
    {
        $variants = ProductVariant::where('status', 'active')
            ->where('scheduled_deactivation_at', '<=', now())
            ->whereNotNull('scheduled_deactivation_at')
            ->get();

        $deactivated = 0;

        foreach ($variants as $variant) {
            try {
                if ($this->transitionStatus($variant, 'inactive')) {
                    $variant->update(['scheduled_deactivation_at' => null]);
                    $deactivated++;
                }
            } catch (\Exception $e) {
                \Log::error("Failed to deactivate scheduled variant {$variant->id}: {$e->getMessage()}");
            }
        }

        return $deactivated;
    }

    /**
     * Lock variant (e.g., when it has active orders).
     *
     * @param  ProductVariant  $variant
     * @param  string  $reason
     * @return bool
     */
    public function lock(ProductVariant $variant, string $reason): bool
    {
        return $variant->update([
            'is_locked' => true,
            'locked_reason' => $reason,
            'locked_at' => now(),
        ]);
    }

    /**
     * Unlock variant.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function unlock(ProductVariant $variant): bool
    {
        return $variant->update([
            'is_locked' => false,
            'locked_reason' => null,
            'locked_at' => null,
        ]);
    }

    /**
     * Check if variant has active orders.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function hasActiveOrders(ProductVariant $variant): bool
    {
        // Check for orders with this variant that are not completed/cancelled
        return DB::table(config('lunar.database.table_prefix') . 'order_lines')
            ->where('purchasable_type', ProductVariant::class)
            ->where('purchasable_id', $variant->id)
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', ['completed', 'cancelled', 'refunded']);
            })
            ->exists();
    }

    /**
     * Clone variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $overrides
     * @return ProductVariant
     */
    public function clone(ProductVariant $variant, array $overrides = []): ProductVariant
    {
        $cloned = $variant->replicate();

        // Set clone-specific fields
        $cloned->status = $overrides['status'] ?? 'draft';
        $cloned->approval_status = 'not_required';
        $cloned->cloned_from_id = $variant->id;
        $cloned->cloned_at = now();
        $cloned->is_locked = false;
        $cloned->locked_reason = null;
        $cloned->locked_at = null;
        $cloned->scheduled_activation_at = null;
        $cloned->scheduled_deactivation_at = null;

        // Generate new SKU if not overridden
        if (empty($overrides['sku'])) {
            $cloned->sku = $this->generateCloneSku($variant);
        }

        // Apply overrides
        foreach ($overrides as $key => $value) {
            if ($cloned->isFillable($key)) {
                $cloned->$key = $value;
            }
        }

        $cloned->save();

        // Clone relationships
        $this->cloneRelationships($variant, $cloned);

        return $cloned->fresh();
    }

    /**
     * Generate SKU for cloned variant.
     *
     * @param  ProductVariant  $variant
     * @return string
     */
    protected function generateCloneSku(ProductVariant $variant): string
    {
        $baseSku = $variant->sku ?? 'VAR';
        $timestamp = now()->format('YmdHis');
        return $baseSku . '-CLONE-' . $timestamp;
    }

    /**
     * Clone variant relationships.
     *
     * @param  ProductVariant  $source
     * @param  ProductVariant  $target
     * @return void
     */
    protected function cloneRelationships(ProductVariant $source, ProductVariant $target): void
    {
        // Clone variant options
        $target->variantOptions()->sync($source->variantOptions->pluck('id')->toArray());

        // Clone media relationships (reference same media)
        foreach ($source->variantMedia as $variantMedia) {
            $target->variantMedia()->create([
                'media_id' => $variantMedia->media_id,
                'media_type' => $variantMedia->media_type,
                'channel_id' => $variantMedia->channel_id,
                'locale' => $variantMedia->locale,
                'primary' => $variantMedia->primary,
                'position' => $variantMedia->position,
                'alt_text' => $variantMedia->alt_text,
                'caption' => $variantMedia->caption,
            ]);
        }
    }

    /**
     * Bulk enable variants.
     *
     * @param  Collection|array  $variantIds
     * @return int Number of variants enabled
     */
    public function bulkEnable($variantIds): int
    {
        $ids = $variantIds instanceof Collection ? $variantIds->toArray() : $variantIds;

        return ProductVariant::whereIn('id', $ids)
            ->where('is_locked', false)
            ->update([
                'enabled' => true,
                'status' => DB::raw("CASE WHEN status = 'inactive' THEN 'active' ELSE status END"),
            ]);
    }

    /**
     * Bulk disable variants.
     *
     * @param  Collection|array  $variantIds
     * @return int Number of variants disabled
     */
    public function bulkDisable($variantIds): int
    {
        $ids = $variantIds instanceof Collection ? $variantIds->toArray() : $variantIds;

        return ProductVariant::whereIn('id', $ids)
            ->where('is_locked', false)
            ->update([
                'enabled' => false,
                'status' => DB::raw("CASE WHEN status = 'active' THEN 'inactive' ELSE status END"),
            ]);
    }

    /**
     * Bulk archive variants.
     *
     * @param  Collection|array  $variantIds
     * @param  bool  $force
     * @return int Number of variants archived
     */
    public function bulkArchive($variantIds, bool $force = false): int
    {
        $ids = $variantIds instanceof Collection ? $variantIds->toArray() : $variantIds;
        $variants = ProductVariant::whereIn('id', $ids)->get();
        $archived = 0;

        foreach ($variants as $variant) {
            try {
                if ($this->archive($variant, $force)) {
                    $archived++;
                }
            } catch (\Exception $e) {
                // Skip variants that can't be archived
                continue;
            }
        }

        return $archived;
    }

    /**
     * Soft delete variant.
     *
     * @param  ProductVariant  $variant
     * @return bool
     */
    public function softDelete(ProductVariant $variant): bool
    {
        // Check if variant has active orders
        if ($this->hasActiveOrders($variant)) {
            throw new \RuntimeException('Cannot delete variant with active orders. Lock it instead.');
        }

        return $variant->delete();
    }

    /**
     * Restore soft-deleted variant.
     *
     * @param  int  $variantId
     * @return bool
     */
    public function restore(int $variantId): bool
    {
        $variant = ProductVariant::withTrashed()->find($variantId);

        if (!$variant) {
            return false;
        }

        return $variant->restore();
    }
}


