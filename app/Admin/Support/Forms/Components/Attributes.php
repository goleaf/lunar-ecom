<?php

namespace App\Admin\Support\Forms\Components;

use Filament\Forms\Components\Component;
use Filament\Forms\Get;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component as Livewire;
use Lunar\Admin\Support\Facades\AttributeData;
use Lunar\Admin\Support\Forms\Components\Attributes as LunarAttributes;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;

/**
 * Extended Attributes component that fixes the return type issue.
 * 
 * This fixes an issue where the schema closure might return a string
 * instead of an array in some edge cases.
 */
class Attributes extends LunarAttributes
{
    protected function setUp(): void
    {
        parent::setUp();

        // Override the schema to ensure it always returns an array
        if (blank($this->childComponents)) {
            $this->schema(function (Get $get, Livewire $livewire, ?Model $record) {
                $modelClass = $this->modelClassOverride ?: $livewire::getResource()::getModel();

                $productTypeId = null;

                $morphMap = $modelClass::morphName();

                $attributeQuery = Attribute::where('attribute_type', $morphMap);

                // Products are unique in that they use product types to map attributes, so we need
                // to try and find the product type ID
                if ($morphMap == Product::morphName()) {
                    $productTypeId = $record?->product_type_id ?: ProductType::first()?->id;

                    // If we have a product type, the attributes should be based off that.
                    if ($productTypeId) {
                        $productType = ProductType::find($productTypeId);
                        if ($productType) {
                            $attributeQuery = $productType->productAttributes();
                        }
                    }
                }

                if ($morphMap == ProductVariant::morphName()) {
                    if ($record::class === Product::modelClass()) {
                        $productTypeId = $record?->product_type_id ?: ProductType::first()?->id;
                    } else {
                        $productTypeId = $record?->product?->product_type_id ?: ProductType::first()?->id;
                    }

                    // If we have a product type, the attributes should be based off that.
                    if ($productTypeId) {
                        $productType = ProductType::find($productTypeId);
                        if ($productType) {
                            $attributeQuery = $productType->variantAttributes();
                        }
                    }
                }

                $attributes = $attributeQuery->orderBy('position')->get();

                $groups = AttributeGroup::where(
                    'attributable_type',
                    $morphMap
                )->orderBy('position', 'asc')
                    ->get()
                    ->map(function ($group) use ($attributes) {
                        return [
                            'model' => $group,
                            'fields' => $attributes->groupBy('attribute_group_id')->get($group->id, collect()),
                        ];
                    })
                    ->filter(fn ($group) => $group['fields']->isNotEmpty());

                $groupComponents = [];

                foreach ($groups as $group) {
                    $sectionFields = [];

                    foreach ($group['fields'] as $field) {
                        $component = AttributeData::getFilamentComponent($field);
                        // Ensure we only add valid components (not strings)
                        if ($component instanceof Component) {
                            $sectionFields[] = $component;
                        }
                    }
                    
                    // Only add section if we have fields
                    if (!empty($sectionFields)) {
                        $groupName = $group['model']->translate('name');
                        // Ensure group name is a string (not an object)
                        if ($groupName instanceof Htmlable) {
                            $groupName = $groupName->toHtml();
                        }
                        if (!is_string($groupName)) {
                            $groupName = (string) $groupName;
                        }
                        
                        $groupComponents[] = \Filament\Forms\Components\Section::make($groupName)
                            ->schema($sectionFields);
                    }
                }

                // Always return an array, never a string
                return is_array($groupComponents) ? $groupComponents : [];
            });
        }
    }
}

