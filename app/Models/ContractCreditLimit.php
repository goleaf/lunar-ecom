<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\B2BContract;

/**
 * Contract Credit Limit Model
 * 
 * Manages credit limits and payment terms for B2B contracts.
 */
class ContractCreditLimit extends Model
{
    use HasFactory;

    protected $table = 'contract_credit_limits';

    protected $fillable = [
        'contract_id',
        'credit_limit',
        'current_balance',
        'payment_terms',
        'payment_terms_days',
        'last_payment_date',
        'meta',
    ];

    protected $casts = [
        'credit_limit' => 'integer',
        'current_balance' => 'integer',
        'payment_terms_days' => 'integer',
        'last_payment_date' => 'date',
        'meta' => 'array',
    ];

    // Payment terms constants
    const TERMS_NET_7 = 'net_7';
    const TERMS_NET_15 = 'net_15';
    const TERMS_NET_30 = 'net_30';
    const TERMS_NET_60 = 'net_60';
    const TERMS_NET_90 = 'net_90';
    const TERMS_IMMEDIATE = 'immediate';

    /**
     * Get the contract that owns this credit limit.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Get available credit.
     */
    public function getAvailableCreditAttribute(): int
    {
        return max(0, $this->credit_limit - $this->current_balance);
    }

    /**
     * Check if credit limit is exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->current_balance >= $this->credit_limit;
    }

    /**
     * Check if there's sufficient credit for an amount.
     */
    public function hasSufficientCredit(int $amount): bool
    {
        return ($this->current_balance + $amount) <= $this->credit_limit;
    }

    /**
     * Add to current balance (when order is placed).
     */
    public function addBalance(int $amount): bool
    {
        if (!$this->hasSufficientCredit($amount)) {
            return false;
        }

        $this->increment('current_balance', $amount);
        return true;
    }

    /**
     * Reduce balance (when payment is received).
     */
    public function reduceBalance(int $amount): void
    {
        $this->decrement('current_balance', max(0, $amount));
        $this->update(['last_payment_date' => now()]);
    }

    /**
     * Get payment due date for an order date.
     */
    public function getPaymentDueDate(\DateTime $orderDate): \DateTime
    {
        $dueDate = clone $orderDate;
        $dueDate->modify("+{$this->payment_terms_days} days");
        return $dueDate;
    }

    /**
     * Get payment terms label.
     */
    public function getPaymentTermsLabel(): string
    {
        return match($this->payment_terms) {
            self::TERMS_NET_7 => 'Net 7',
            self::TERMS_NET_15 => 'Net 15',
            self::TERMS_NET_30 => 'Net 30',
            self::TERMS_NET_60 => 'Net 60',
            self::TERMS_NET_90 => 'Net 90',
            self::TERMS_IMMEDIATE => 'Immediate',
            default => "Net {$this->payment_terms_days}",
        };
    }
}


