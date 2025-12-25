<?php

namespace App\Admin\Extensions\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;

/**
 * Example extension for CustomerGroupPricing Relation Manager in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/relation-managers
 * 
 * Relation manager extensions allow you to customize Lunar's admin panel relation managers,
 * such as adding custom form fields and table columns.
 */
class ExampleCustomerGroupPricingRelationManagerExtension extends RelationManagerExtension
{
    /**
     * Extend the form schema.
     * 
     * Add custom form fields to the relation manager form.
     */
    public function extendForm(Form $form): Form
    {
        return $form->schema([
            ...$form->getComponents(withHidden: true), // Get all existing components (including hidden ones)
            
            // Add custom form fields here
            // TextInput::make('custom_column')
            //     ->label('Custom Column')
            //     ->required(),
        ]);
    }

    /**
     * Extend the table schema.
     * 
     * Add custom table columns to the relation manager table.
     */
    public function extendTable(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(), // Get all existing columns
            
            // Add custom table columns here
            // TextColumn::make('product_code')
            //     ->label('Product Code')
            //     ->searchable()
            //     ->sortable(),
        ]);
    }
}


