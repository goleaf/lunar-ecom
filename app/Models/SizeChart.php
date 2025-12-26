<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SizeChart extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'size_charts';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'size_guide_id',
        'size_name',
        'size_code',
        'size_order',
        'measurements',
        'size_min',
        'size_max',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'measurements' => 'array',
        'size_order' => 'integer',
        'size_min' => 'integer',
        'size_max' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the size guide.
     */
    public function sizeGuide(): BelongsTo
    {
        return $this->belongsTo(SizeGuide::class, 'size_guide_id');
    }

    /**
     * Get a specific measurement.
     */
    public function getMeasurement(string $key): ?float
    {
        return $this->measurements[$key] ?? null;
    }

    /**
     * Check if size matches a measurement range.
     */
    public function matchesMeasurement(string $key, float $value, float $tolerance = 2.0): bool
    {
        $measurement = $this->getMeasurement($key);
        
        if ($measurement === null) {
            return false;
        }

        return abs($measurement - $value) <= $tolerance;
    }
}


