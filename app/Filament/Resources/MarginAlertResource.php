<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarginAlertResource\Pages;
use App\Models\MarginAlert;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarginAlertResource extends Resource
{
    protected static ?string $model = MarginAlert::class;

    protected static ?string $slug = 'ops-margin-alerts';

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Margin Alerts';

    protected static ?int $navigationSort = 90;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['variant.product']);
    }

    public static function form(Form $form): Form
    {
        // Read-only resource (view page uses disabled form fields).
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (MarginAlert $record): string => (string) (
                        $record->variant?->product?->translateAttribute('name')
                        ?? $record->variant?->product?->translateAttribute('name')
                        ?? 'N/A'
                    ))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('variant_sku')
                    ->label('Variant')
                    ->getStateUsing(fn (MarginAlert $record): string => $record->variant?->sku ?? "#{$record->product_variant_id}")
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('alert_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_margin_percentage')
                    ->label('Margin %')
                    ->sortable(),

                Tables\Columns\TextColumn::make('threshold_margin_percentage')
                    ->label('Threshold %')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('current_price')
                    ->label('Price')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->boolean()
                    ->label('Resolved')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('alert_type')
                    ->options([
                        'low_margin' => 'Low margin',
                        'negative_margin' => 'Negative margin',
                        'margin_threshold' => 'Threshold breach',
                    ]),
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Resolved'),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->requiresConfirmation()
                    ->visible(fn (MarginAlert $record): bool => ! $record->is_resolved)
                    ->action(fn (MarginAlert $record) => $record->resolve()),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarginAlerts::route('/'),
            'view' => Pages\ViewMarginAlert::route('/{record}'),
        ];
    }
}

