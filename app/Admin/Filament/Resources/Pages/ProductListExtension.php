<?php

namespace App\Admin\Filament\Resources\Pages;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Lunar\Panel\Filament\Resources\ProductResource\Pages\ListProducts;

/**
 * Extended List Products page with bulk actions and enhanced UX.
 */
class ProductListExtension extends ListProducts
{
    /**
     * Configure the table.
     */
    protected function getTableActions(): array
    {
        return [
            ...parent::getTableActions(),
            Tables\Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('storefront.products.show', [
                    'product' => $record->slug ?? $record->id,
                    'preview' => true,
                ]))
                ->openUrlInNewTab(),
            
            Tables\Actions\Action::make('clone')
                ->label('Clone')
                ->icon('heroicon-o-document-duplicate')
                ->action(function ($record) {
                    \App\Admin\Actions\CloneProductAction::make()->action($record);
                })
                ->requiresConfirmation(),
        ];
    }

    /**
     * Get header actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            Actions\Action::make('bulkEdit')
                ->label('Bulk Edit Attributes')
                ->icon('heroicon-o-pencil-square')
                ->action(function () {
                    $this->dispatch('open-modal', ['id' => 'bulk-attribute-editor']);
                }),
        ];
    }

    /**
     * Get bulk actions.
     */
    protected function getTableBulkActions(): array
    {
        return [
            ...parent::getTableBulkActions(),
            BulkAction::make('bulkEditAttributes')
                ->label('Bulk Edit Attributes')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    \Filament\Forms\Components\Select::make('attribute')
                        ->label('Attribute')
                        ->options(\Lunar\Models\Attribute::pluck('name', 'id'))
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('value')
                        ->label('Value')
                        ->required(),
                ])
                ->action(function ($records, array $data) {
                    foreach ($records as $product) {
                        $attribute = \Lunar\Models\Attribute::find($data['attribute']);
                        if ($attribute) {
                            $product->setAttributeValue($attribute->handle, $data['value']);
                        }
                    }
                }),
        ];
    }
}
