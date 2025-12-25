<?php

namespace App\Admin\Extensions\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

/**
 * Example extension for Product Resource in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/resources
 * 
 * Resource extensions allow you to customize Lunar's admin panel resources,
 * such as adding custom form fields, table columns, relation managers, and pages.
 */
class ExampleProductResourceExtension extends ResourceExtension
{
    /**
     * Extend the form schema.
     * 
     * Add custom form fields to the resource form.
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
     * Add custom table columns to the resource table.
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

    /**
     * Add or modify relation managers.
     * 
     * Relation managers are standard Filament relation managers.
     * See: https://filamentphp.com/docs/3.x/panels/resources/relation-managers#creating-a-relation-manager
     */
    public function getRelations(array $managers): array
    {
        return [
            ...$managers, // Include existing relation managers
            
            // Add custom relation managers here
            // MyCustomProductRelationManager::class,
        ];
    }

    /**
     * Add or modify pages.
     * 
     * Pages are standard Filament pages.
     * See: https://filamentphp.com/docs/3.x/panels/pages#creating-a-page
     */
    public function extendPages(array $pages): array
    {
        return [
            ...$pages, // Include existing pages
            
            // Add custom pages here
            // 'my-page-route-name' => MyPage::route('/{record}/my-page'),
        ];
    }

    /**
     * Extend the sub-navigation.
     * 
     * Add custom pages to the sub-navigation menu.
     * See: https://filamentphp.com/docs/3.x/panels/pages#creating-a-page
     */
    public function extendSubNavigation(array $nav): array
    {
        return [
            ...$nav, // Include existing navigation items
            
            // Add custom navigation items here
            // MyPage::class,
        ];
    }
}


