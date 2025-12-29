<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LowStockAlertResource\Pages\ListLowStockAlerts;
use App\Filament\Resources\LowStockAlertResource\Pages\ViewLowStockAlert;
use App\Models\LowStockAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlertResource extends Resource
{
    protected static ?string $model = LowStockAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Low Stock Alerts';

    protected static ?int $navigationSort = 35;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['productVariant.product', 'warehouse', 'inventoryLevel', 'resolver']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Alert')
                    ->schema([
                        Forms\Components\TextInput::make('productVariant.sku')
                            ->label('SKU')
                            ->disabled(),

                        Forms\Components\TextInput::make('warehouse.name')
                            ->label('Warehouse')
                            ->disabled(),

                        Forms\Components\TextInput::make('current_quantity')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('reorder_point')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\Toggle::make('is_resolved')
                            ->disabled(),

                        Forms\Components\TextInput::make('resolved_at')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notification')
                    ->schema([
                        Forms\Components\Toggle::make('notification_sent')
                            ->disabled(),

                        Forms\Components\TextInput::make('notification_sent_at')
                            ->disabled(),
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
                    ->getStateUsing(fn (LowStockAlert $record): string => (string) (
                        $record->productVariant?->product?->translate('name')
                            ?? "Product #{$record->productVariant?->product_id}"
                    ))
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('Current')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Reorder point')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('is_resolved')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Resolved' : 'Open')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),

                Tables\Columns\IconColumn::make('notification_sent')
                    ->label('Notified')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Resolved'),

                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (LowStockAlert $record): bool => !$record->is_resolved)
                    ->action(function (LowStockAlert $record): void {
                        $record->forceFill([
                            'is_resolved' => true,
                            'resolved_at' => now(),
                            // NOTE: resolved_by references users; Filament uses staff auth.
                            // Keep null unless you explicitly link staff -> user.
                            'resolved_by' => null,
                        ])->save();
                    }),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        /** @var LowStockAlert $record */
                        foreach ($records as $record) {
                            if ($record->is_resolved) {
                                continue;
                            }

                            $record->forceFill([
                                'is_resolved' => true,
                                'resolved_at' => now(),
                                'resolved_by' => null,
                            ])->save();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLowStockAlerts::route('/'),
            'view' => ViewLowStockAlert::route('/{record}'),
        ];
    }
}

