<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'default_discount_stack_policy',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Group types
    const TYPE_B2C = 'B2C';
    const TYPE_B2B = 'B2B';
    const TYPE_VIP = 'VIP';
    const TYPE_STAFF = 'Staff';
    const TYPE_PARTNER = 'Partner';
    const TYPE_OTHER = 'Other';

    /**
     * Get users in this group.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }
}


