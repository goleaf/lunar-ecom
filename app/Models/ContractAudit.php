<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;
use App\Models\B2BContract;
use App\Models\PriceList;
use App\Models\ContractPrice;
use App\Models\User;

/**
 * Contract Audit Model
 * 
 * Tracks all changes and usage of contracts for auditing purposes.
 */
class ContractAudit extends Model
{
    use HasFactory;

    protected $table = 'contract_audits';

    protected $fillable = [
        'contract_id',
        'price_list_id',
        'contract_price_id',
        'audit_type',
        'action',
        'description',
        'old_values',
        'new_values',
        'user_id',
        'order_id',
        'margin_percentage',
        'quantity',
        'total_value',
        'meta',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'margin_percentage' => 'decimal:2',
        'quantity' => 'integer',
        'total_value' => 'integer',
        'meta' => 'array',
    ];

    // Audit type constants
    const TYPE_PRICE_CHANGE = 'price_change';
    const TYPE_USAGE = 'usage';
    const TYPE_MARGIN_ANALYSIS = 'margin_analysis';
    const TYPE_EXPIRY_ALERT = 'expiry_alert';
    const TYPE_CONTRACT_CHANGE = 'contract_change';

    // Action constants
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_PRICE_CHANGED = 'price_changed';
    const ACTION_USED = 'used';
    const ACTION_EXPIRED = 'expired';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';

    /**
     * Get the contract being audited.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Get the price list (if applicable).
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    /**
     * Get the contract price (if applicable).
     */
    public function contractPrice(): BelongsTo
    {
        return $this->belongsTo(ContractPrice::class, 'contract_price_id');
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order (for usage tracking).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Log a price change.
     */
    public static function logPriceChange(
        ContractPrice $price,
        array $oldValues,
        array $newValues,
        ?User $user = null
    ): self {
        return self::create([
            'contract_id' => $price->priceList->contract_id,
            'price_list_id' => $price->price_list_id,
            'contract_price_id' => $price->id,
            'audit_type' => self::TYPE_PRICE_CHANGE,
            'action' => self::ACTION_PRICE_CHANGED,
            'description' => "Price changed for {$price->pricing_type}",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user?->id,
        ]);
    }

    /**
     * Log contract usage (when order is placed).
     */
    public static function logUsage(
        B2BContract $contract,
        Order $order,
        int $quantity,
        int $totalValue,
        ?PriceList $priceList = null
    ): self {
        return self::create([
            'contract_id' => $contract->id,
            'price_list_id' => $priceList?->id,
            'audit_type' => self::TYPE_USAGE,
            'action' => self::ACTION_USED,
            'description' => "Contract used for order #{$order->reference}",
            'order_id' => $order->id,
            'quantity' => $quantity,
            'total_value' => $totalValue,
        ]);
    }

    /**
     * Log margin analysis.
     */
    public static function logMarginAnalysis(
        B2BContract $contract,
        float $marginPercentage,
        int $quantity,
        int $totalValue,
        ?PriceList $priceList = null
    ): self {
        return self::create([
            'contract_id' => $contract->id,
            'price_list_id' => $priceList?->id,
            'audit_type' => self::TYPE_MARGIN_ANALYSIS,
            'action' => self::ACTION_CREATED,
            'description' => "Margin analysis: {$marginPercentage}% margin",
            'margin_percentage' => $marginPercentage,
            'quantity' => $quantity,
            'total_value' => $totalValue,
        ]);
    }

    /**
     * Log expiry alert.
     */
    public static function logExpiryAlert(B2BContract $contract): self
    {
        return self::create([
            'contract_id' => $contract->id,
            'audit_type' => self::TYPE_EXPIRY_ALERT,
            'action' => self::ACTION_EXPIRED,
            'description' => "Contract expired on {$contract->valid_to->format('Y-m-d')}",
        ]);
    }

    /**
     * Scope to get audits by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('audit_type', $type);
    }

    /**
     * Scope to get audits for a contract.
     */
    public function scopeForContract($query, int $contractId)
    {
        return $query->where('contract_id', $contractId);
    }
}


