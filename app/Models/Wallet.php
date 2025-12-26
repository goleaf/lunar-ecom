<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'pending_balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Add credit to wallet.
     */
    public function credit(float $amount, string $reason, array $metadata = []): WalletTransaction
    {
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => WalletTransaction::TYPE_CREDIT,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Debit from wallet.
     */
    public function debit(float $amount, string $reason, array $metadata = []): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->decrement('balance', $amount);

        return $this->transactions()->create([
            'type' => WalletTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Hold amount (for pending transactions).
     */
    public function hold(float $amount, string $reason, array $metadata = []): WalletTransaction
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $this->decrement('balance', $amount);
        $this->increment('pending_balance', $amount);

        return $this->transactions()->create([
            'type' => WalletTransaction::TYPE_HOLD,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Release held amount.
     */
    public function release(float $amount, string $reason, array $metadata = []): WalletTransaction
    {
        if ($this->pending_balance < $amount) {
            throw new \Exception('Insufficient pending balance');
        }

        $this->decrement('pending_balance', $amount);
        $this->increment('balance', $amount);

        return $this->transactions()->create([
            'type' => WalletTransaction::TYPE_RELEASE,
            'amount' => $amount,
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}


