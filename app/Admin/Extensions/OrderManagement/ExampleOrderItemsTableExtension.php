<?php

namespace App\Admin\Extensions\OrderManagement;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable;

/**
 * Example extension for Order Items Table component in Lunar admin panel.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/admin/extending/order-management#extending-orderitemstable
 * 
 * Order items table extensions allow you to customize the order lines table,
 * including adding custom columns and modifying table configuration.
 */
class ExampleOrderItemsTableExtension extends OrderItemsTable
{
    /**
     * Extend the order lines table columns.
     * 
     * Add custom columns to the order lines table.
     */
    public function extendOrderLinesTableColumns(): array
    {
        return [
            // Add custom table columns here
            // TextColumn::make('custom_field')
            //     ->label('Custom Field')
            //     ->searchable()
            //     ->sortable(),
        ];
    }

    /**
     * Extend the table configuration.
     * 
     * Customize the table (filters, actions, etc.).
     */
    public function extendTable(): Table
    {
        // Customize table configuration
        // Example: Add filters, bulk actions, etc.
        // return Table::make()
        //     ->filters([...])
        //     ->actions([...]);
    }
}


