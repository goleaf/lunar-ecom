<?php

namespace App\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Panel\Filament\Resources\ProductResource;

/**
 * Example extension for the Product Resource.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/overview#extending-resources
 * 
 * Extensions allow you to add custom functionality to Lunar's admin panel resources,
 * such as custom form fields, table columns, filters, or relationships.
 */
class ProductResourceExtension extends ProductResource
{
    /**
     * Form configuration.
     * 
     * You can override this method to customize the form.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...parent::form($form)->getComponents(),
                // Add custom form fields here
                // Forms\Components\Section::make('Custom Section')
                //     ->schema([
                //         Forms\Components\TextInput::make('custom_field')
                //             ->label('Custom Field'),
                //     ]),
            ]);
    }

    /**
     * Table configuration.
     * 
     * You can override this method to customize the table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ...parent::table($table)->getColumns(),
                // Add custom table columns here
                // Tables\Columns\TextColumn::make('custom_field')
                //     ->label('Custom Field'),
            ])
            ->filters([
                ...parent::table($table)->getFilters(),
                // Add custom filters here
            ])
            ->actions([
                ...parent::table($table)->getActions(),
                // Add custom actions here
            ])
            ->bulkActions([
                ...parent::table($table)->getBulkActions(),
                // Add custom bulk actions here
            ]);
    }

    // Add any other methods you need to extend the ProductResource behavior
}


