<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductWorkflow;
use App\Models\ProductWorkflowHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing product workflow (draft → review → published).
 */
class ProductWorkflowService
{
    /**
     * Get or create workflow for product.
     *
     * @param  Product  $product
     * @return ProductWorkflow
     */
    public function getOrCreateWorkflow(Product $product): ProductWorkflow
    {
        return ProductWorkflow::firstOrCreate(
            ['product_id' => $product->id],
            ['status' => 'draft']
        );
    }

    /**
     * Submit product for review.
     *
     * @param  Product  $product
     * @param  string|null  $notes
     * @return ProductWorkflow
     */
    public function submitForReview(Product $product, ?string $notes = null): ProductWorkflow
    {
        return DB::transaction(function () use ($product, $notes) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            if (!$workflow->canSubmit()) {
                throw new \Exception('Product cannot be submitted for review in current status.');
            }
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'review',
                'previous_status' => $previousStatus,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'submission_notes' => $notes,
            ]);
            
            // Record history
            $this->recordHistory($product, $workflow, 'submitted', $previousStatus, 'review', $notes);
            
            return $workflow->fresh();
        });
    }

    /**
     * Approve product.
     *
     * @param  Product  $product
     * @param  string|null  $notes
     * @return ProductWorkflow
     */
    public function approve(Product $product, ?string $notes = null): ProductWorkflow
    {
        return DB::transaction(function () use ($product, $notes) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            if (!$workflow->canApprove()) {
                throw new \Exception('Product cannot be approved in current status.');
            }
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'approved',
                'previous_status' => $previousStatus,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);
            
            // Record history
            $this->recordHistory($product, $workflow, 'approved', $previousStatus, 'approved', $notes);
            
            return $workflow->fresh();
        });
    }

    /**
     * Reject product.
     *
     * @param  Product  $product
     * @param  string  $reason
     * @return ProductWorkflow
     */
    public function reject(Product $product, string $reason): ProductWorkflow
    {
        return DB::transaction(function () use ($product, $reason) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            if ($workflow->status !== 'review') {
                throw new \Exception('Only products in review can be rejected.');
            }
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'rejected',
                'previous_status' => $previousStatus,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            
            // Record history
            $this->recordHistory($product, $workflow, 'rejected', $previousStatus, 'rejected', $reason);
            
            return $workflow->fresh();
        });
    }

    /**
     * Publish product.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface|null  $expiresAt
     * @param  bool  $autoArchiveOnExpiry
     * @return ProductWorkflow
     */
    public function publish(
        Product $product,
        ?\DateTimeInterface $expiresAt = null,
        bool $autoArchiveOnExpiry = false
    ): ProductWorkflow {
        return DB::transaction(function () use ($product, $expiresAt, $autoArchiveOnExpiry) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            if (!$workflow->canPublish()) {
                throw new \Exception('Product cannot be published in current status.');
            }
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'published',
                'previous_status' => $previousStatus,
                'published_at' => now(),
                'expires_at' => $expiresAt,
                'auto_archive_on_expiry' => $autoArchiveOnExpiry,
            ]);
            
            // Update product status
            $product->update(['status' => 'published']);
            
            // Record history
            $this->recordHistory($product, $workflow, 'published', $previousStatus, 'published');
            
            return $workflow->fresh();
        });
    }

    /**
     * Unpublish product.
     *
     * @param  Product  $product
     * @return ProductWorkflow
     */
    public function unpublish(Product $product): ProductWorkflow
    {
        return DB::transaction(function () use ($product) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'draft',
                'previous_status' => $previousStatus,
            ]);
            
            // Update product status
            $product->update(['status' => 'draft']);
            
            // Record history
            $this->recordHistory($product, $workflow, 'unpublished', $previousStatus, 'draft');
            
            return $workflow->fresh();
        });
    }

    /**
     * Archive product.
     *
     * @param  Product  $product
     * @return ProductWorkflow
     */
    public function archive(Product $product): ProductWorkflow
    {
        return DB::transaction(function () use ($product) {
            $workflow = $this->getOrCreateWorkflow($product);
            
            $previousStatus = $workflow->status;
            
            $workflow->update([
                'status' => 'archived',
                'previous_status' => $previousStatus,
                'archived_at' => now(),
            ]);
            
            // Update product status
            $product->update(['status' => 'archived']);
            
            // Record history
            $this->recordHistory($product, $workflow, 'archived', $previousStatus, 'archived');
            
            return $workflow->fresh();
        });
    }

    /**
     * Set product expiration.
     *
     * @param  Product  $product
     * @param  \DateTimeInterface  $expiresAt
     * @param  bool  $autoArchiveOnExpiry
     * @return ProductWorkflow
     */
    public function setExpiration(
        Product $product,
        \DateTimeInterface $expiresAt,
        bool $autoArchiveOnExpiry = true
    ): ProductWorkflow {
        $workflow = $this->getOrCreateWorkflow($product);
        
        $workflow->update([
            'expires_at' => $expiresAt,
            'auto_archive_on_expiry' => $autoArchiveOnExpiry,
        ]);
        
        return $workflow->fresh();
    }

    /**
     * Process expired products.
     *
     * @return int  Number of products processed
     */
    public function processExpiredProducts(): int
    {
        $expiredWorkflows = ProductWorkflow::expired()
            ->where('auto_archive_on_expiry', true)
            ->where('status', 'published')
            ->with('product')
            ->get();
        
        $count = 0;
        
        foreach ($expiredWorkflows as $workflow) {
            try {
                $this->archive($workflow->product);
                $this->recordHistory(
                    $workflow->product,
                    $workflow,
                    'auto_archived',
                    'published',
                    'archived',
                    'Product expired and auto-archived'
                );
                $count++;
            } catch (\Exception $e) {
                \Log::error('Failed to auto-archive expired product', [
                    'product_id' => $workflow->product_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $count;
    }

    /**
     * Record workflow history.
     *
     * @param  Product  $product
     * @param  ProductWorkflow  $workflow
     * @param  string  $action
     * @param  string|null  $fromStatus
     * @param  string|null  $toStatus
     * @param  string|null  $notes
     * @param  array|null  $metadata
     * @return ProductWorkflowHistory
     */
    protected function recordHistory(
        Product $product,
        ProductWorkflow $workflow,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $notes = null,
        ?array $metadata = null
    ): ProductWorkflowHistory {
        return ProductWorkflowHistory::create([
            'product_id' => $product->id,
            'workflow_id' => $workflow->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'user_id' => Auth::id(),
            'notes' => $notes,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Get workflow history for product.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(Product $product, int $limit = 50)
    {
        return ProductWorkflowHistory::where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->with('user')
            ->get();
    }
}

