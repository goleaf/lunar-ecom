<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantRelationship;
use Illuminate\Support\Collection;

/**
 * Service for managing variant relationships.
 * 
 * Handles:
 * - Cross-variant linking (same product, different attributes)
 * - Replacement variants
 * - Upgrade / downgrade variants
 * - Accessory variants
 * - Bundle component variants
 */
class VariantRelationshipService
{
    /**
     * Create a relationship between variants.
     *
     * @param  ProductVariant  $variant
     * @param  ProductVariant  $relatedVariant
     * @param  string  $relationshipType
     * @param  array  $options
     * @return VariantRelationship
     */
    public function createRelationship(
        ProductVariant $variant,
        ProductVariant $relatedVariant,
        string $relationshipType,
        array $options = []
    ): VariantRelationship {
        // Prevent self-relationship
        if ($variant->id === $relatedVariant->id) {
            throw new \InvalidArgumentException('A variant cannot be related to itself.');
        }

        // Validate relationship type
        $validTypes = [
            'cross_variant',
            'replacement',
            'upgrade',
            'downgrade',
            'accessory',
            'bundle_component',
            'compatible',
            'alternative',
        ];

        if (!in_array($relationshipType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid relationship type: {$relationshipType}");
        }

        // Check if relationship already exists
        $existing = VariantRelationship::where('variant_id', $variant->id)
            ->where('related_variant_id', $relatedVariant->id)
            ->where('relationship_type', $relationshipType)
            ->first();

        if ($existing) {
            // Update existing relationship
            $existing->update($options);
            return $existing->fresh();
        }

        // Create relationship
        $relationship = VariantRelationship::create([
            'variant_id' => $variant->id,
            'related_variant_id' => $relatedVariant->id,
            'relationship_type' => $relationshipType,
            'label' => $options['label'] ?? null,
            'description' => $options['description'] ?? null,
            'sort_order' => $options['sort_order'] ?? 0,
            'is_active' => $options['is_active'] ?? true,
            'is_bidirectional' => $options['is_bidirectional'] ?? false,
            'metadata' => $options['metadata'] ?? null,
        ]);

        // Create reverse relationship if bidirectional
        if ($relationship->is_bidirectional) {
            VariantRelationship::create([
                'variant_id' => $relatedVariant->id,
                'related_variant_id' => $variant->id,
                'relationship_type' => $relationshipType,
                'label' => $options['label'] ?? null,
                'description' => $options['description'] ?? null,
                'sort_order' => $options['sort_order'] ?? 0,
                'is_active' => $options['is_active'] ?? true,
                'is_bidirectional' => true,
                'metadata' => $options['metadata'] ?? null,
            ]);
        }

        return $relationship;
    }

    /**
     * Get all relationships for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  string|null  $relationshipType
     * @param  bool  $includeInactive
     * @return Collection
     */
    public function getRelationships(
        ProductVariant $variant,
        ?string $relationshipType = null,
        bool $includeInactive = false
    ): Collection {
        $query = VariantRelationship::where('variant_id', $variant->id)
            ->with(['relatedVariant', 'variant'])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($relationshipType) {
            $query->where('relationship_type', $relationshipType);
        }

        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    /**
     * Get cross-variant relationships (same product, different attributes).
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getCrossVariants(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'cross_variant')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get replacement variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getReplacements(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'replacement')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get upgrade variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getUpgrades(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'upgrade')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get downgrade variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getDowngrades(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'downgrade')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get accessory variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getAccessories(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'accessory')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get bundle component variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getBundleComponents(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'bundle_component')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get compatible variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getCompatible(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'compatible')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Get alternative variants.
     *
     * @param  ProductVariant  $variant
     * @return Collection
     */
    public function getAlternatives(ProductVariant $variant): Collection
    {
        return $this->getRelationships($variant, 'alternative')
            ->map(fn($rel) => $rel->relatedVariant)
            ->filter();
    }

    /**
     * Delete a relationship.
     *
     * @param  ProductVariant  $variant
     * @param  ProductVariant  $relatedVariant
     * @param  string|null  $relationshipType
     * @return bool
     */
    public function deleteRelationship(
        ProductVariant $variant,
        ProductVariant $relatedVariant,
        ?string $relationshipType = null
    ): bool {
        $query = VariantRelationship::where('variant_id', $variant->id)
            ->where('related_variant_id', $relatedVariant->id);

        if ($relationshipType) {
            $query->where('relationship_type', $relationshipType);
        }

        $relationships = $query->get();

        // Delete reverse relationships if bidirectional
        foreach ($relationships as $relationship) {
            if ($relationship->is_bidirectional) {
                VariantRelationship::where('variant_id', $relatedVariant->id)
                    ->where('related_variant_id', $variant->id)
                    ->where('relationship_type', $relationship->relationship_type)
                    ->delete();
            }
        }

        return $query->delete() > 0;
    }

    /**
     * Auto-generate cross-variant relationships for same product.
     *
     * @param  ProductVariant  $variant
     * @return int Number of relationships created
     */
    public function autoGenerateCrossVariants(ProductVariant $variant): int
    {
        $product = $variant->product;
        $created = 0;

        // Get all other variants of the same product
        $otherVariants = $product->variants()
            ->where('id', '!=', $variant->id)
            ->where('status', 'active')
            ->get();

        foreach ($otherVariants as $otherVariant) {
            // Check if relationship already exists
            $exists = VariantRelationship::where('variant_id', $variant->id)
                ->where('related_variant_id', $otherVariant->id)
                ->where('relationship_type', 'cross_variant')
                ->exists();

            if (!$exists) {
                $this->createRelationship(
                    $variant,
                    $otherVariant,
                    'cross_variant',
                    [
                        'is_bidirectional' => true,
                        'label' => 'Same product, different options',
                    ]
                );
                $created++;
            }
        }

        return $created;
    }

    /**
     * Get all related variants grouped by type.
     *
     * @param  ProductVariant  $variant
     * @return array
     */
    public function getAllRelationshipsGrouped(ProductVariant $variant): array
    {
        return [
            'cross_variants' => $this->getCrossVariants($variant),
            'replacements' => $this->getReplacements($variant),
            'upgrades' => $this->getUpgrades($variant),
            'downgrades' => $this->getDowngrades($variant),
            'accessories' => $this->getAccessories($variant),
            'bundle_components' => $this->getBundleComponents($variant),
            'compatible' => $this->getCompatible($variant),
            'alternatives' => $this->getAlternatives($variant),
        ];
    }
}


