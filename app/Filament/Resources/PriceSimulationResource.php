<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceSimulationResource\Pages;
use App\Models\PriceSimulation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceSimulationResource extends Resource
{
    protected static ?string $model = PriceSimulation::class;

    protected static ?string $slug = 'ops-price-simulations';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Price Simulations';

    protected static ?int $navigationSort = 95;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['variant.product', 'currency', 'channel', 'customerGroup', 'customer']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Simulation')
                ->schema([
                    Forms\Components\TextInput::make('product_variant_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('currency_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('quantity')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('base_price')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('final_price')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Textarea::make('applied_rules')
                        ->label('Applied rules (JSON)')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('pricing_breakdown')
                        ->label('Pricing breakdown (JSON)')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('simulation_context')
                        ->label('Context')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('variant_sku')
                    ->label('Variant')
                    ->getStateUsing(fn (PriceSimulation $record): string => $record->variant?->sku ?? "#{$record->product_variant_id}")
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Curr.')
                    ->getStateUsing(fn (PriceSimulation $record): string => $record->currency?->code ?? (string) $record->currency_id)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('Base')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_price')
                    ->label('Final')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('customer_id')
                    ->label('Customer-specific')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('customer_id'),
                        false: fn (Builder $query) => $query->whereNull('customer_id'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceSimulations::route('/'),
            'view' => Pages\ViewPriceSimulation::route('/{record}'),
        ];
    }
}

