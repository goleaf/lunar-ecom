<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\AttributeFactory;
use Database\Factories\AttributeGroupFactory;
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
        $productGroupData = AttributeGroupFactory::new()
            ->state([
                'handle' => 'product',
                'name' => ['en' => 'Product'],
                'attributable_type' => Product::class,
                'position' => 0,
            ])
            ->make()
            ->getAttributes();
        $productGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            Arr::only($productGroupData, ['name', 'attributable_type', 'position'])
        );

        $filterGroupData = AttributeGroupFactory::new()
            ->state([
                'handle' => 'filters',
                'name' => ['en' => 'Filters'],
                'attributable_type' => Product::class,
                'position' => 1,
            ])
            ->make()
            ->getAttributes();
        $filterGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'filters'],
            Arr::only($filterGroupData, ['name', 'attributable_type', 'position'])
        );

        $specsGroupData = AttributeGroupFactory::new()
            ->state([
                'handle' => 'specifications',
                'name' => ['en' => 'Specifications'],
                'attributable_type' => Product::class,
                'position' => 2,
            ])
            ->make()
            ->getAttributes();
        $specsGroup = AttributeGroup::firstOrCreate(
            ['handle' => 'specifications'],
            Arr::only($specsGroupData, ['name', 'attributable_type', 'position'])
        );

        // Color Attribute (Select/Color type)
        $colorData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $filterGroup->id,
            'position' => 1,
            'name' => ['en' => 'Color'],
            'handle' => 'color',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'searchable' => true,
            'filterable' => true,
            'system' => false,
            'display_order' => 1,
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'color', 'attribute_type' => 'product'],
            Arr::except($colorData, ['handle', 'attribute_type'])
        );

        // Size Attribute (Select type)
        $sizeData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $filterGroup->id,
            'position' => 2,
            'name' => ['en' => 'Size'],
            'handle' => 'size',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'searchable' => true,
            'filterable' => true,
            'system' => false,
            'display_order' => 2,
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'size', 'attribute_type' => 'product'],
            Arr::except($sizeData, ['handle', 'attribute_type'])
        );

        // Material Attribute (Select type)
        $materialData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $specsGroup->id,
            'position' => 1,
            'name' => ['en' => 'Material'],
            'handle' => 'material',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'searchable' => true,
            'filterable' => true,
            'system' => false,
            'display_order' => 3,
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'material', 'attribute_type' => 'product'],
            Arr::except($materialData, ['handle', 'attribute_type'])
        );

        // Features Attribute (Multiselect/Boolean flags)
        $featuresData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $specsGroup->id,
            'position' => 2,
            'name' => ['en' => 'Features'],
            'handle' => 'features',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'searchable' => true,
            'filterable' => true,
            'system' => false,
            'display_order' => 4,
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'features', 'attribute_type' => 'product'],
            Arr::except($featuresData, ['handle', 'attribute_type'])
        );

        // Weight Attribute (Number type)
        $weightData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $specsGroup->id,
            'position' => 3,
            'name' => ['en' => 'Weight'],
            'handle' => 'weight',
            'type' => \Lunar\FieldTypes\Number::class,
            'required' => false,
            'searchable' => false,
            'filterable' => true,
            'system' => false,
            'display_order' => 5,
            'unit' => 'kg',
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'weight', 'attribute_type' => 'product'],
            Arr::except($weightData, ['handle', 'attribute_type'])
        );

        // Warranty Period Attribute (Number type)
        $warrantyData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $specsGroup->id,
            'position' => 4,
            'name' => ['en' => 'Warranty Period'],
            'handle' => 'warranty_period',
            'type' => \Lunar\FieldTypes\Number::class,
            'required' => false,
            'searchable' => false,
            'filterable' => true,
            'system' => false,
            'display_order' => 6,
            'unit' => 'months',
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'warranty_period', 'attribute_type' => 'product'],
            Arr::except($warrantyData, ['handle', 'attribute_type'])
        );

        // Condition Attribute (Select type)
        $conditionData = AttributeFactory::new()->state([
            'attribute_type' => 'product',
            'attribute_group_id' => $filterGroup->id,
            'position' => 3,
            'name' => ['en' => 'Condition'],
            'handle' => 'condition',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'searchable' => true,
            'filterable' => true,
            'system' => false,
            'display_order' => 7,
            'section' => 'main',
        ])->make()->getAttributes();
        Attribute::updateOrCreate(
            ['handle' => 'condition', 'attribute_type' => 'product'],
            Arr::except($conditionData, ['handle', 'attribute_type'])
        );

        $this->command->info('Product attributes seeded successfully!');
    }
}
