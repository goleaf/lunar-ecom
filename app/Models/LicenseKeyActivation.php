<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for tracking license key activations.
 */
class LicenseKeyActivation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'license_key_activations';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'license_key_id',
        'user_id',
        'device_id',
        'device_name',
        'ip_address',
        'activated_at',
        'deactivated_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * License key relationship.
     *
     * @return BelongsTo
     */
    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(VariantLicenseKey::class, 'license_key_id');
    }

    /**
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Deactivate activation.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $updated = $this->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        if ($updated) {
            $this->licenseKey->decrement('current_activations');
        }

        return $updated;
    }
}


