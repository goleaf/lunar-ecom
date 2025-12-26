<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for managing variant license keys.
 */
class VariantLicenseKey extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_license_keys';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'order_line_id',
        'license_key',
        'status',
        'expiry_date',
        'max_activations',
        'current_activations',
        'notes',
        'allocated_at',
        'activated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
        'max_activations' => 'integer',
        'current_activations' => 'integer',
        'allocated_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Order line relationship.
     *
     * @return BelongsTo
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\OrderLine::class);
    }

    /**
     * Activations relationship.
     *
     * @return HasMany
     */
    public function activations(): HasMany
    {
        return $this->hasMany(LicenseKeyActivation::class, 'license_key_id');
    }

    /**
     * Scope available license keys.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Check if license can be activated.
     *
     * @return bool
     */
    public function canBeActivated(): bool
    {
        if ($this->status !== 'activated' && $this->status !== 'allocated') {
            return false;
        }

        if ($this->expiry_date && $this->expiry_date->isPast()) {
            return false;
        }

        return $this->current_activations < $this->max_activations;
    }

    /**
     * Activate license key.
     *
     * @param  array  $data
     * @return LicenseKeyActivation
     */
    public function activate(array $data = []): LicenseKeyActivation
    {
        if (!$this->canBeActivated()) {
            throw new \Exception('License key cannot be activated');
        }

        $activation = $this->activations()->create([
            'user_id' => $data['user_id'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'device_name' => $data['device_name'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'activated_at' => now(),
            'is_active' => true,
        ]);

        $this->increment('current_activations');
        
        if ($this->status === 'available' || $this->status === 'allocated') {
            $this->update([
                'status' => 'activated',
                'activated_at' => now(),
            ]);
        }

        return $activation;
    }

    /**
     * Generate license key.
     *
     * @param  string  $format
     * @return string
     */
    public static function generate(string $format = 'XXXX-XXXX-XXXX-XXXX'): string
    {
        $key = '';
        for ($i = 0; $i < strlen($format); $i++) {
            if ($format[$i] === 'X') {
                $key .= strtoupper(dechex(rand(0, 15)));
            } else {
                $key .= $format[$i];
            }
        }
        return $key;
    }
}


