<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'title',
        'content',
        'summary',
        'is_active',
        'is_current',
        'effective_date',
        'created_by',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_current' => 'boolean',
            'effective_date' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get current active policy
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true)
            ->where('is_active', true)
            ->orderBy('effective_date', 'desc');
    }

    /**
     * Scope to get active policies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->orderBy('effective_date', 'desc');
    }

    /**
     * Set this policy as current and deactivate others
     */
    public function setAsCurrent(): void
    {
        static::where('id', '!=', $this->id)->update(['is_current' => false]);
        $this->update(['is_current' => true, 'is_active' => true]);
    }
}
