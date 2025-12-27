<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lunar\Models\Customer;
use Lunar\Models\Currency;
use App\Models\User;

/**
 * B2B Contract Model
 * 
 * Represents a B2B contract between the company and a customer.
 * Contracts define pricing, terms, and rules for B2B transactions.
 */
class B2BContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'b2b_contracts';

    protected $fillable = [
        'contract_id',
        'customer_id',
        'name',
        'description',
        'valid_from',
        'valid_to',
        'currency_id',
        'priority',
        'status',
        'approval_state',
        'approved_by',
        'approved_at',
        'approval_notes',
        'terms_reference',
        'meta',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
        'approved_at' => 'datetime',
        'priority' => 'integer',
        'meta' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Approval state constants
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';

    /**
     * Get the customer that owns this contract.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the currency for this contract.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who approved this contract.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all price lists for this contract.
     */
    public function priceLists(): HasMany
    {
        return $this->hasMany(PriceList::class, 'contract_id');
    }

    /**
     * Get active price lists for this contract.
     */
    public function activePriceLists(): HasMany
    {
        return $this->priceLists()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            });
    }

    /**
     * Get all contract rules.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(ContractRule::class, 'contract_id');
    }

    /**
     * Get active contract rules.
     */
    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true);
    }

    /**
     * Get the credit limit for this contract.
     */
    public function creditLimit(): HasMany
    {
        return $this->hasMany(ContractCreditLimit::class, 'contract_id');
    }

    /**
     * Get purchase orders for this contract.
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(ContractPurchaseOrder::class, 'contract_id');
    }

    /**
     * Get audit logs for this contract.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(ContractAudit::class, 'contract_id');
    }

    /**
     * Get sales reps assigned to this contract.
     */
    public function salesReps(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contract_sales_reps', 'contract_id', 'user_id')
            ->withPivot('is_primary', 'commission_rate', 'meta')
            ->withTimestamps();
    }

    /**
     * Get primary sales rep.
     */
    public function primarySalesRep(): BelongsToMany
    {
        return $this->salesReps()->wherePivot('is_primary', true);
    }

    /**
     * Get shared carts for this contract.
     */
    public function sharedCarts(): HasMany
    {
        return $this->hasMany(ContractSharedCart::class, 'contract_id');
    }

    /**
     * Check if contract is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->approval_state === self::APPROVAL_APPROVED
            && $this->valid_from <= now()
            && ($this->valid_to === null || $this->valid_to >= now());
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_to !== null && $this->valid_to < now();
    }

    /**
     * Check if contract is valid for a given date.
     */
    public function isValidForDate(\DateTime $date): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->approval_state === self::APPROVAL_APPROVED
            && $this->valid_from <= $date
            && ($this->valid_to === null || $this->valid_to >= $date);
    }

    /**
     * Approve the contract.
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        return $this->update([
            'approval_state' => self::APPROVAL_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Reject the contract.
     */
    public function reject(User $rejector, ?string $notes = null): bool
    {
        return $this->update([
            'approval_state' => self::APPROVAL_REJECTED,
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Scope to get active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('approval_state', self::APPROVAL_APPROVED)
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now());
            });
    }

    /**
     * Scope to get contracts for a customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get contracts by priority (highest first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}


