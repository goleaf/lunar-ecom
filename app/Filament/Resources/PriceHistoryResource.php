<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceHistoryResource\Pages\ListPriceHistories;
use App\Filament\Resources\PriceHistoryResource\Pages\ViewPriceHistory;
use App\Models\PriceHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceHistoryResource extends Resource
{
    protected static ?string $model = PriceHistory::class;

    protected static ?string $slug = 'ops-price-history';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Price History';

    protected static ?int $navigationSort = 80;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'variant', 'priceMatrix', 'changedBy']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Price change')
                ->schema([
                    Forms\Components\TextInput::make('product_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('product_variant_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('price_matrix_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('change_type')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('currency_code')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('old_price')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('new_price')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DateTimePicker::make('changed_at')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Textarea::make('change_reason')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('change_notes')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('context')
                        ->label('Context (JSON)')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('Advanced history (optional)')
                ->schema([
                    Forms\Components\TextInput::make('currency_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('price')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Minor units (e.g. cents).'),

                    Forms\Components\TextInput::make('compare_at_price')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Minor units (e.g. cents).'),

                    Forms\Components\TextInput::make('channel_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('customer_group_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('pricing_layer')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('pricing_rule_id')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DateTimePicker::make('effective_from')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\DateTimePicker::make('effective_to')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Textarea::make('change_metadata')
                        ->label('Change metadata (JSON)')
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : (string) ($state ?? ''))
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(3)
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (PriceHistory $record): string => (string) (
                        $record->product?->translateAttribute('name') ?? "Product #{$record->product_id}"
                    ))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('product_variant_id')
                    ->label('Variant')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('change_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('old_price')
                    ->label('Old')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('new_price')
                    ->label('New')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency_code')
                    ->label('Curr.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('changed_by_email')
                    ->label('Changed by')
                    ->getStateUsing(fn (PriceHistory $record): string => $record->changedBy?->email ?? 'System')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('changed_at')
                    ->dateTime()
                    ->label('Changed')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('change_type')
                    ->options([
                        'manual' => 'Manual',
                        'matrix' => 'Matrix',
                        'import' => 'Import',
                        'bulk' => 'Bulk',
                        'scheduled' => 'Scheduled',
                    ]),
                Tables\Filters\TernaryFilter::make('product_variant_id')
                    ->label('Variant-specific')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('product_variant_id'),
                        false: fn (Builder $query) => $query->whereNull('product_variant_id'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('changed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPriceHistories::route('/'),
            'view' => ViewPriceHistory::route('/{record}'),
        ];
    }
}

