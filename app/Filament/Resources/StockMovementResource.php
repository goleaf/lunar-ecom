<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'productVariant.product',
                'warehouse',
                'inventoryLevel',
                'creator',
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Movement')
                    ->schema([
                        Forms\Components\TextInput::make('movement_date')
                            ->disabled(),

                        Forms\Components\TextInput::make('type')
                            ->disabled(),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('quantity_before')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('quantity_after')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Context')
                    ->schema([
                        Forms\Components\TextInput::make('productVariant.sku')
                            ->label('SKU')
                            ->disabled(),

                        Forms\Components\TextInput::make('warehouse.name')
                            ->label('Warehouse')
                            ->disabled(),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Reference')
                            ->disabled(),

                        Forms\Components\TextInput::make('actor_type')
                            ->disabled(),

                        Forms\Components\TextInput::make('actor_identifier')
                            ->disabled(),

                        Forms\Components\Textarea::make('reason')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('movement_date')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale' => 'danger',
                        'return' => 'success',
                        'manual_adjustment' => 'warning',
                        'import' => 'info',
                        'damage' => 'danger',
                        'transfer' => 'gray',
                        'correction' => 'gray',
                        'reservation' => 'warning',
                        'release' => 'success',
                        'loss' => 'danger',
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->color(fn (StockMovement $record): string => $record->quantity >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (StockMovement $record): string => (string) (
                        $record->productVariant?->product?->translate('name')
                            ?? "Product #{$record->productVariant?->product_id}"
                    ))
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('actor_name')
                    ->label('Actor')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reason')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name'),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'sale' => 'Sale',
                        'return' => 'Return',
                        'manual_adjustment' => 'Manual adjustment',
                        'import' => 'Import',
                        'damage' => 'Damage',
                        'transfer' => 'Transfer',
                        'correction' => 'Correction',
                        'reservation' => 'Reservation',
                        'release' => 'Release',
                        'loss' => 'Loss',
                        'in' => 'In',
                        'out' => 'Out',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('movement_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}

