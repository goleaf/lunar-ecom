<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Customer;

class CookieConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'customer_id',
        'necessary',
        'analytics',
        'marketing',
        'preferences',
        'custom_categories',
        'ip_address',
        'user_agent',
        'consented_at',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'necessary' => 'boolean',
            'analytics' => 'boolean',
            'marketing' => 'boolean',
            'preferences' => 'boolean',
            'custom_categories' => 'array',
            'consented_at' => 'datetime',
            'last_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Check if user has consented to a specific category
     */
    public function hasConsented(string $category): bool
    {
        return match ($category) {
            'necessary' => $this->necessary,
            'analytics' => $this->analytics,
            'marketing' => $this->marketing,
            'preferences' => $this->preferences,
            default => in_array($category, $this->custom_categories ?? []),
        };
    }

    /**
     * Get all consented categories
     */
    public function getConsentedCategories(): array
    {
        $categories = [];
        
        if ($this->necessary) {
            $categories[] = 'necessary';
        }
        if ($this->analytics) {
            $categories[] = 'analytics';
        }
        if ($this->marketing) {
            $categories[] = 'marketing';
        }
        if ($this->preferences) {
            $categories[] = 'preferences';
        }
        
        if ($this->custom_categories) {
            $categories = array_merge($categories, $this->custom_categories);
        }
        
        return $categories;
    }
}
