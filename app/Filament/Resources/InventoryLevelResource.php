<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLevelResource\Pages;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use App\Services\StockService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryLevelResource extends Resource
{
    protected static ?string $model = InventoryLevel::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Inventory Levels';

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['productVariant.product', 'warehouse']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stock (read-only totals)')
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('reserved_quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('incoming_quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('damaged_quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('preorder_quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Replenishment')
                    ->schema([
                        Forms\Components\TextInput::make('reorder_point')
                            ->numeric()
                            ->minValue(0)
                            ->default(10),

                        Forms\Components\TextInput::make('safety_stock_level')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('reorder_quantity')
                            ->numeric()
                            ->minValue(0)
                            ->default(50),

                        Forms\Components\TextInput::make('backorder_limit')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productVariant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (InventoryLevel $record): string => (string) (
                        $record->productVariant?->product?->translate('name')
                            ?? "Product #{$record->productVariant?->product_id}"
                    ))
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'backorder' => 'gray',
                        'preorder' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_stock' => 'In stock',
                        'low_stock' => 'Low stock',
                        'out_of_stock' => 'Out of stock',
                        'backorder' => 'Backorder',
                        'preorder' => 'Preorder',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity delta')
                            ->helperText('Use positive numbers to add stock and negative numbers to remove stock.')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('reason')
                            ->maxLength(255)
                            ->default('Manual adjustment'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000),
                    ])
                    ->action(function (InventoryLevel $record, array $data): void {
                        app(StockService::class)->adjustStock(
                            $record->productVariant,
                            (int) $record->warehouse_id,
                            (int) $data['quantity'],
                            (string) ($data['reason'] ?? 'Manual adjustment'),
                            $data['notes'] ?? null
                        );
                    }),

                Tables\Actions\Action::make('transfer_stock')
                    ->label('Transfer')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->form([
                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('To warehouse')
                            ->options(fn (InventoryLevel $record): array => Warehouse::query()
                                ->where('is_active', true)
                                ->where('id', '!=', $record->warehouse_id)
                                ->orderBy('priority')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000),
                    ])
                    ->action(function (InventoryLevel $record, array $data): void {
                        app(StockService::class)->transferStock(
                            $record->productVariant,
                            (int) $record->warehouse_id,
                            (int) $data['to_warehouse_id'],
                            (int) $data['quantity'],
                            $data['notes'] ?? null
                        );
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryLevels::route('/'),
            'edit' => Pages\EditInventoryLevel::route('/{record}/edit'),
        ];
    }
}

