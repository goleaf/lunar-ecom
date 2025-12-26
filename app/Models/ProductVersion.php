<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Base\BaseModel;

class ProductVersion extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'version_number',
        'version_name',
        'version_notes',
        'product_data',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'version_number' => 'integer',
        'product_data' => 'array',
    ];

    /**
     * Get the product that owns this version.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who created this version.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the parent version (if this is a revision of another version).
     *
     * @return BelongsTo|null
     */
    public function parentVersion(): ?BelongsTo
    {
        return $this->belongsTo(ProductVersion::class, 'parent_version_id');
    }

    /**
     * Get child versions (revisions of this version).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function childVersions()
    {
        return $this->hasMany(ProductVersion::class, 'parent_version_id');
    }

    /**
     * Restore product to this version.
     *
     * @return Product
     */
    public function restore(): Product
    {
        $product = $this->product;
        
        if ($this->product_data) {
            // Restore product attributes from snapshot
            // Exclude fields that shouldn't be restored
            $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
            
            foreach ($this->product_data as $key => $value) {
                if (in_array($key, $excludedFields)) {
                    continue;
                }

                if ($key === 'attribute_data') {
                    $decoded = $value;
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                    }

                    if (is_array($decoded)) {
                        $attributeData = collect();
                        foreach ($decoded as $handle => $item) {
                            $fieldType = $item['field_type'] ?? null;
                            $fieldValue = $item['value'] ?? null;
                            if ($fieldType && class_exists($fieldType)) {
                                $attributeData->put($handle, new $fieldType($fieldValue));
                            }
                        }
                        $product->attribute_data = $attributeData;
                        continue;
                    }
                }

                if ($key === 'custom_meta' && is_string($value)) {
                    $product->custom_meta = json_decode($value, true);
                    continue;
                }

                $product->{$key} = $value;
            }
            
            $product->save();
            
            // Create a new version snapshot of the restored state
            $product->createVersion('Restored from Version ' . $this->version_number, 'Restored from version snapshot');
        }
        
        return $product;
    }
}
