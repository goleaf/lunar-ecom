<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Cart;
use App\Models\B2BContract;
use App\Models\User;

/**
 * Contract Shared Cart Model
 * 
 * Manages shared carts for B2B contracts.
 * Allows multiple users to collaborate on a single cart.
 */
class ContractSharedCart extends Model
{
    use HasFactory;

    protected $table = 'lunar_contract_shared_carts';

    protected $fillable = [
        'contract_id',
        'cart_id',
        'name',
        'description',
        'created_by',
        'shared_with',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'shared_with' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Get the contract that owns this shared cart.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Get the cart.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the user who created this shared cart.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a user can access this shared cart.
     */
    public function canBeAccessedBy(User $user): bool
    {
        // Creator can always access
        if ($this->created_by === $user->id) {
            return true;
        }

        // Check if user is in shared_with list
        $sharedWith = $this->shared_with ?? [];
        return in_array($user->id, $sharedWith);
    }

    /**
     * Add a user to the shared_with list.
     */
    public function shareWith(User $user): void
    {
        $sharedWith = $this->shared_with ?? [];
        if (!in_array($user->id, $sharedWith)) {
            $sharedWith[] = $user->id;
            $this->update(['shared_with' => $sharedWith]);
        }
    }

    /**
     * Remove a user from the shared_with list.
     */
    public function unshareWith(User $user): void
    {
        $sharedWith = $this->shared_with ?? [];
        $sharedWith = array_values(array_diff($sharedWith, [$user->id]));
        $this->update(['shared_with' => $sharedWith]);
    }
}


