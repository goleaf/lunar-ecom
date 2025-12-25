<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ProductWorkflow model for managing product workflow states.
 */
class ProductWorkflow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_workflows';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'status',
        'previous_status',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'published_at',
        'archived_at',
        'submission_notes',
        'approval_notes',
        'rejection_reason',
        'expires_at',
        'auto_archive_on_expiry',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_archive_on_expiry' => 'boolean',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * User who submitted.
     *
     * @return BelongsTo
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    /**
     * User who approved.
     *
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * User who rejected.
     *
     * @return BelongsTo
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
    }

    /**
     * Workflow history relationship.
     *
     * @return HasMany
     */
    public function history(): HasMany
    {
        return $this->hasMany(ProductWorkflowHistory::class, 'workflow_id');
    }

    /**
     * Scope to get products in draft status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get products in review status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReview($query)
    {
        return $query->where('status', 'review');
    }

    /**
     * Scope to get approved products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get published products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get expired products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->whereNotNull('expires_at');
    }

    /**
     * Check if workflow is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if product can be submitted for review.
     *
     * @return bool
     */
    public function canSubmit(): bool
    {
        return $this->status === 'draft' || $this->status === 'rejected';
    }

    /**
     * Check if product can be approved.
     *
     * @return bool
     */
    public function canApprove(): bool
    {
        return $this->status === 'review';
    }

    /**
     * Check if product can be published.
     *
     * @return bool
     */
    public function canPublish(): bool
    {
        return $this->status === 'approved' || $this->status === 'draft';
    }
}

