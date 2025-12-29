<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchAnalyticResource\Pages\ListSearchAnalytics;
use App\Filament\Resources\SearchAnalyticResource\Pages\ViewSearchAnalytic;
use App\Models\SearchAnalytic;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SearchAnalyticResource extends Resource
{
    protected static ?string $model = SearchAnalytic::class;

    protected static ?string $slug = 'ops-search-analytics';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Insights';

    protected static ?string $navigationLabel = 'Search Analytics';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['clickedProduct', 'user']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('search_term')
                    ->label('Query')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),

                Tables\Columns\TextColumn::make('result_count')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('zero_results')
                    ->boolean()
                    ->label('Zero')
                    ->sortable(),

                Tables\Columns\TextColumn::make('clicked_product_name')
                    ->label('Clicked')
                    ->getStateUsing(fn (SearchAnalytic $record): ?string => $record->clickedProduct?->translate('name'))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('session_id')
                    ->label('Session')
                    ->limit(10)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('searched_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('zero_results')
                    ->label('Zero results'),

                Tables\Filters\TernaryFilter::make('clicked_product_id')
                    ->label('Has click')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('clicked_product_id'),
                        false: fn (Builder $query) => $query->whereNull('clicked_product_id'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('searched_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSearchAnalytics::route('/'),
            'view' => ViewSearchAnalytic::route('/{record}'),
        ];
    }
}

