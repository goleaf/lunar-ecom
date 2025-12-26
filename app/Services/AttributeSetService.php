<?php

namespace App\Services;

use App\Models\AttributeSet;
use App\Models\AttributeGroup;
use App\Models\Attribute;
use Lunar\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Enhanced Attribute Set Service.
 * 
 * Manages:
 * - Attribute sets per product type
 * - Reusable attribute groups
 * - Inheritance between sets
 * - Conditional visibility
 * - Attribute ordering
 */
class AttributeSetService
{
    /**
     * Create an attribute set.
     *
     * @param  array  $data
     * @return AttributeSet
     */
    public function createAttributeSet(array $data): AttributeSet
    {
        return DB::transaction(function () use ($data) {
            $set = AttributeSet::create([
                'name' => $data['name'],
                'handle' => $data['handle'],
                'code' => $data['code'] ?? $this->generateCode($data['name']),
                'description' => $data['description'] ?? null,
                'product_type_id' => $data['product_type_id'] ?? null,
                'parent_set_id' => $data['parent_set_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'position' => $data['position'] ?? 0,
            ]);

            // Attach groups if provided
            if (isset($data['group_ids'])) {
                $this->attachGroups($set, $data['group_ids'], $data['group_positions'] ?? []);
            }

            return $set;
        });
    }

    /**
     * Update an attribute set.
     *
     * @param  AttributeSet  $set
     * @param  array  $data
     * @return AttributeSet
     */
    public function updateAttributeSet(AttributeSet $set, array $data): AttributeSet
    {
        return DB::transaction(function () use ($set, $data) {
            $set->update(array_filter([
                'name' => $data['name'] ?? null,
                'handle' => $data['handle'] ?? null,
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'product_type_id' => $data['product_type_id'] ?? null,
                'parent_set_id' => $data['parent_set_id'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'is_default' => $data['is_default'] ?? null,
                'position' => $data['position'] ?? null,
            ], fn($value) => $value !== null));

            // Update groups if provided
            if (isset($data['group_ids'])) {
                $this->syncGroups($set, $data['group_ids'], $data['group_positions'] ?? []);
            }

            return $set->fresh();
        });
    }

    /**
     * Create a reusable attribute group.
     *
     * @param  array  $data
     * @return AttributeGroup
     */
    public function createAttributeGroup(array $data): AttributeGroup
    {
        return DB::transaction(function () use ($data) {
            $group = AttributeGroup::create([
                'name' => $data['name'],
                'handle' => $data['handle'],
                'code' => $data['code'] ?? $this->generateCode($data['name']),
                'description' => $data['description'] ?? null,
                'is_reusable' => $data['is_reusable'] ?? true,
                'is_active' => $data['is_active'] ?? true,
                'position' => $data['position'] ?? 0,
            ]);

            // Attach attributes if provided
            if (isset($data['attribute_ids'])) {
                $this->attachAttributes($group, $data['attribute_ids'], $data['attribute_positions'] ?? []);
            }

            return $group;
        });
    }

    /**
     * Update an attribute group.
     *
     * @param  AttributeGroup  $group
     * @param  array  $data
     * @return AttributeGroup
     */
    public function updateAttributeGroup(AttributeGroup $group, array $data): AttributeGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update(array_filter([
                'name' => $data['name'] ?? null,
                'handle' => $data['handle'] ?? null,
                'code' => $data['code'] ?? null,
                'description' => $data['description'] ?? null,
                'is_reusable' => $data['is_reusable'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'position' => $data['position'] ?? null,
            ], fn($value) => $value !== null));

            // Update attributes if provided
            if (isset($data['attribute_ids'])) {
                $this->syncAttributes($group, $data['attribute_ids'], $data['attribute_positions'] ?? []);
            }

            return $group->fresh();
        });
    }

    /**
     * Attach groups to an attribute set.
     *
     * @param  AttributeSet  $set
     * @param  array  $groupIds
     * @param  array  $positions
     * @return void
     */
    public function attachGroups(AttributeSet $set, array $groupIds, array $positions = []): void
    {
        $syncData = [];
        foreach ($groupIds as $index => $groupId) {
            $syncData[$groupId] = [
                'position' => $positions[$index] ?? $index,
                'is_visible' => true,
            ];
        }

        $set->groups()->sync($syncData);
    }

    /**
     * Sync groups for an attribute set.
     *
     * @param  AttributeSet  $set
     * @param  array  $groupIds
     * @param  array  $positions
     * @return void
     */
    public function syncGroups(AttributeSet $set, array $groupIds, array $positions = []): void
    {
        $syncData = [];
        foreach ($groupIds as $index => $groupId) {
            $syncData[$groupId] = [
                'position' => $positions[$index] ?? $index,
                'is_visible' => true,
            ];
        }

        $set->groups()->sync($syncData);
    }

    /**
     * Attach attributes to an attribute group.
     *
     * @param  AttributeGroup  $group
     * @param  array  $attributeIds
     * @param  array  $positions
     * @return void
     */
    public function attachAttributes(AttributeGroup $group, array $attributeIds, array $positions = []): void
    {
        $syncData = [];
        foreach ($attributeIds as $index => $attributeId) {
            $syncData[$attributeId] = [
                'position' => $positions[$index] ?? $index,
                'is_visible' => true,
                'is_required' => false,
            ];
        }

        $group->attributes()->sync($syncData);
    }

    /**
     * Sync attributes for an attribute group.
     *
     * @param  AttributeGroup  $group
     * @param  array  $attributeIds
     * @param  array  $positions
     * @return void
     */
    public function syncAttributes(AttributeGroup $group, array $attributeIds, array $positions = []): void
    {
        $syncData = [];
        foreach ($attributeIds as $index => $attributeId) {
            $syncData[$attributeId] = [
                'position' => $positions[$index] ?? $index,
                'is_visible' => true,
                'is_required' => false,
            ];
        }

        $group->attributes()->sync($syncData);
    }

    /**
     * Set group visibility conditions.
     *
     * @param  AttributeSet  $set
     * @param  int  $groupId
     * @param  array  $conditions
     * @return void
     */
    public function setGroupVisibilityConditions(AttributeSet $set, int $groupId, array $conditions): void
    {
        $set->groups()->updateExistingPivot($groupId, [
            'visibility_conditions' => $conditions,
        ]);
    }

    /**
     * Set attribute visibility conditions.
     *
     * @param  AttributeGroup  $group
     * @param  int  $attributeId
     * @param  array  $conditions
     * @return void
     */
    public function setAttributeVisibilityConditions(AttributeGroup $group, int $attributeId, array $conditions): void
    {
        $group->attributes()->updateExistingPivot($attributeId, [
            'visibility_conditions' => $conditions,
        ]);
    }

    /**
     * Check visibility conditions.
     *
     * @param  array  $conditions
     * @param  array  $context
     * @return bool
     */
    public function checkVisibilityConditions(array $conditions, array $context = []): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $contextValue = $context[$field] ?? null;

            $result = match($operator) {
                'equals' => $contextValue === $value,
                'not_equals' => $contextValue !== $value,
                'contains' => is_array($contextValue) && in_array($value, $contextValue),
                'not_contains' => is_array($contextValue) && !in_array($value, $contextValue),
                'greater_than' => is_numeric($contextValue) && is_numeric($value) && $contextValue > $value,
                'less_than' => is_numeric($contextValue) && is_numeric($value) && $contextValue < $value,
                'is_empty' => empty($contextValue),
                'is_not_empty' => !empty($contextValue),
                default => false,
            };

            // If any condition fails, return false
            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get visible groups for a set.
     *
     * @param  AttributeSet  $set
     * @param  array  $context
     * @return Collection
     */
    public function getVisibleGroups(AttributeSet $set, array $context = []): Collection
    {
        return $set->groups->filter(function ($group) use ($context) {
            $pivot = $group->pivot;
            
            // Check if explicitly hidden
            if (!$pivot->is_visible) {
                return false;
            }

            // Check visibility conditions
            if ($pivot->visibility_conditions) {
                return $this->checkVisibilityConditions($pivot->visibility_conditions, $context);
            }

            return true;
        });
    }

    /**
     * Get visible attributes for a group.
     *
     * @param  AttributeGroup  $group
     * @param  array  $context
     * @return Collection
     */
    public function getVisibleAttributes(AttributeGroup $group, array $context = []): Collection
    {
        return $group->attributes->filter(function ($attribute) use ($context) {
            $pivot = $attribute->pivot;
            
            // Check if explicitly hidden
            if (!$pivot->is_visible) {
                return false;
            }

            // Check visibility conditions
            if ($pivot->visibility_conditions) {
                return $this->checkVisibilityConditions($pivot->visibility_conditions, $context);
            }

            return true;
        });
    }

    /**
     * Get attribute set for a product type.
     *
     * @param  ProductType  $productType
     * @return AttributeSet|null
     */
    public function getAttributeSetForProductType(ProductType $productType): ?AttributeSet
    {
        return AttributeSet::forProductType($productType->id)
            ->default()
            ->active()
            ->first();
    }

    /**
     * Get all attributes for a product type (including inherited).
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getAttributesForProductType(ProductType $productType): Collection
    {
        $set = $this->getAttributeSetForProductType($productType);
        
        if (!$set) {
            return collect();
        }

        return $set->getAllAttributes();
    }

    /**
     * Reorder groups in a set.
     *
     * @param  AttributeSet  $set
     * @param  array  $groupIds
     * @return void
     */
    public function reorderGroups(AttributeSet $set, array $groupIds): void
    {
        foreach ($groupIds as $position => $groupId) {
            $set->groups()->updateExistingPivot($groupId, [
                'position' => $position,
            ]);
        }
    }

    /**
     * Reorder attributes in a group.
     *
     * @param  AttributeGroup  $group
     * @param  array  $attributeIds
     * @return void
     */
    public function reorderAttributes(AttributeGroup $group, array $attributeIds): void
    {
        foreach ($attributeIds as $position => $attributeId) {
            $group->attributes()->updateExistingPivot($attributeId, [
                'position' => $position,
            ]);
        }
    }

    /**
     * Generate code from name.
     *
     * @param  string  $name
     * @return string
     */
    protected function generateCode(string $name): string
    {
        return strtoupper(str_replace([' ', '-'], '_', $name));
    }
}
