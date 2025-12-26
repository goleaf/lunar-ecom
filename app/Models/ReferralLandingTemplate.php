<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralLandingTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'is_default',
        'supported_locales',
        'content',
        'noindex',
        'og_image_url',
        'version',
        'meta',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'supported_locales' => 'array',
        'content' => 'array',
        'noindex' => 'boolean',
        'version' => 'integer',
        'meta' => 'array',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';

    protected static function booted(): void
    {
        static::updating(function (self $template) {
            // Increment cache version on any change.
            if ($template->isDirty()) {
                $template->version = (int) $template->version + 1;
            }
        });
    }
}


