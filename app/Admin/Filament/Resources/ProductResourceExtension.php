<?php

namespace App\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Panel\Filament\Resources\ProductResource;

/**
 * Extended Product Resource with enhanced UX features.
 */
class ProductResourceExtension extends ProductResource
{
    /**
     * Form configuration with real-time validation.
     */
    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                ...parent::form($form)->getComponents(),
                
                // Add real-time validation to key fields
                Section::make('Enhanced Fields')
                    ->schema([
                        TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Real-time SKU validation
                                if ($state && \App\Models\Product::where('sku', $state)->exists()) {
                                    $set('sku', $state . '-' . time());
                                }
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Table configuration with enhanced actions.
     */
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                ...parent::table($table)->getColumns(),
            ])
            ->filters([
                ...parent::table($table)->getFilters(),
            ])
            ->actions([
                ...parent::table($table)->getActions(),
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
            ])
            ->bulkActions([
                ...parent::table($table)->getBulkActions(),
            ]);
    }
}
