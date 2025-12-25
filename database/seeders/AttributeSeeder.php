<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Product;

/**
 * Seeder for common product attributes.
 * 
 * Creates filterable attributes for:
 * - Brand (already handled by Brand model, but can be used as attribute)
 * - Color
 * - Size
 * - Material
 * - Features
 */
class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create attribute groups
        $productGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            [
                'name' => ['en' => 'Product'],
                'attributable_type' => Product::class,
                'position' => 0,
            ]
        );

        $filterGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'filters'],
            [
                'name' => ['en' => 'Filters'],
                'attributable_type' => Product::class,
                'position' => 1,
            ]
        );

        $specsGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'specifications'],
            [
                'name' => ['en' => 'Specifications'],
                'attributable_type' => Product::class,
                'position' => 2,
            ]
        );

        // Color Attribute (Select/Color type)
        Attribute::firstOrCreate(
            ['handle' => 'color'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $filterGroup->id,
                'position' => 1,
                'name' => ['en' => 'Color'],
                'type' => \Lunar\FieldTypes\Text::class, // Can be changed to Color type if available
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'display_order' => 1,
                'section' => 'main',
            ]
        );

        // Size Attribute (Select type)
        Attribute::firstOrCreate(
            ['handle' => 'size'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $filterGroup->id,
                'position' => 2,
                'name' => ['en' => 'Size'],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'display_order' => 2,
                'section' => 'main',
            ]
        );

        // Material Attribute (Select type)
        Attribute::firstOrCreate(
            ['handle' => 'material'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $specsGroup->id,
                'position' => 1,
                'name' => ['en' => 'Material'],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'display_order' => 3,
                'section' => 'main',
            ]
        );

        // Features Attribute (Multiselect/Boolean flags)
        Attribute::firstOrCreate(
            ['handle' => 'features'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $specsGroup->id,
                'position' => 2,
                'name' => ['en' => 'Features'],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'display_order' => 4,
                'section' => 'main',
            ]
        );

        // Weight Attribute (Number type)
        Attribute::firstOrCreate(
            ['handle' => 'weight'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $specsGroup->id,
                'position' => 3,
                'name' => ['en' => 'Weight'],
                'type' => \Lunar\FieldTypes\Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'display_order' => 5,
                'unit' => 'kg',
                'section' => 'main',
            ]
        );

        // Warranty Period Attribute (Number type)
        Attribute::firstOrCreate(
            ['handle' => 'warranty_period'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $specsGroup->id,
                'position' => 4,
                'name' => ['en' => 'Warranty Period'],
                'type' => \Lunar\FieldTypes\Number::class,
                'required' => false,
                'searchable' => false,
                'filterable' => true,
                'system' => false,
                'display_order' => 6,
                'unit' => 'months',
                'section' => 'main',
            ]
        );

        // Condition Attribute (Select type)
        Attribute::firstOrCreate(
            ['handle' => 'condition'],
            [
                'attribute_type' => 'product',
                'attribute_group_id' => $filterGroup->id,
                'position' => 3,
                'name' => ['en' => 'Condition'],
                'type' => \Lunar\FieldTypes\Text::class,
                'required' => false,
                'searchable' => true,
                'filterable' => true,
                'system' => false,
                'display_order' => 7,
                'section' => 'main',
            ]
        );

        $this->command->info('Product attributes seeded successfully!');
    }
}

