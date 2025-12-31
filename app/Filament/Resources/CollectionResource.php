<?php

namespace App\Filament\Resources;

use App\Admin\Support\Forms\Components\Attributes;
use App\Filament\Resources\CollectionResource\Pages\CreateCollection;
use App\Filament\Resources\CollectionResource\Pages\EditCollection;
use App\Filament\Resources\CollectionResource\Pages\ListCollections;
use App\Filament\Resources\CollectionResource\Pages\ViewCollection;
use App\Filament\Resources\CollectionResource\RelationManagers\ProductMetadataRelationManager;
use App\Filament\Resources\CollectionResource\RelationManagers\SmartRulesRelationManager;
use App\Models\Collection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\CollectionGroup;

class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Collections';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['group']);
    }

    public static function form(Form $form): Form
    {
        $collectionTypeOptions = [
            // "Management" roles (as introduced by 2025_12_25_170000_add_collection_management_fields).
            'manual' => 'Manual',
            'bestsellers' => 'Bestsellers',
            'new_arrivals' => 'New arrivals',
            'featured' => 'Featured',
            'seasonal' => 'Seasonal',
            'custom' => 'Custom',

            // Legacy/service-driven values (kept for backwards compatibility).
            'standard' => 'Standard',
            'cross_sell' => 'Cross sell',
            'up_sell' => 'Up sell',
            'related' => 'Related',
            'bundle' => 'Bundle',
        ];

        return $form
            ->schema([
                Forms\Components\Section::make('Core')
                    ->schema([
                        Forms\Components\Select::make('collection_group_id')
                            ->label('Collection group')
                            ->options(fn (): array => CollectionGroup::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->options([
                                'static' => 'Static',
                                'dynamic' => 'Dynamic',
                            ])
                            ->default('static')
                            ->required(),

                        Forms\Components\TextInput::make('sort')
                            ->helperText('Lunar sort format "<field>:<direction>" (e.g. "position:asc").')
                            ->default('position:asc')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Merchandising')
                    ->schema([
                        Forms\Components\Select::make('collection_type')
                            ->label('Collection type')
                            ->options($collectionTypeOptions)
                            ->searchable()
                            ->default('manual')
                            ->required(),

                        Forms\Components\Toggle::make('auto_assign')
                            ->label('Auto assign products')
                            ->default(false),

                        Forms\Components\Textarea::make('assignment_rules')
                            ->label('Assignment rules (JSON)')
                            ->rows(4)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                            ->dehydrateStateUsing(function ($state) {
                                if (! is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);
                                return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                            })
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('max_products')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Forms\Components\Select::make('sort_by')
                            ->options([
                                'created_at' => 'Created date',
                                'price' => 'Price',
                                'name' => 'Name',
                                'popularity' => 'Popularity',
                                'sales_count' => 'Sales count',
                                'rating' => 'Rating',
                            ])
                            ->default('created_at')
                            ->required(),

                        Forms\Components\Select::make('sort_direction')
                            ->options([
                                'asc' => 'Ascending',
                                'desc' => 'Descending',
                            ])
                            ->default('desc')
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\Toggle::make('show_on_homepage')
                            ->label('Show on homepage')
                            ->default(false),

                        Forms\Components\TextInput::make('homepage_position')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),

                        Forms\Components\Select::make('display_style')
                            ->options([
                                'grid' => 'Grid',
                                'list' => 'List',
                                'carousel' => 'Carousel',
                            ])
                            ->default('grid')
                            ->required(),

                        Forms\Components\TextInput::make('products_per_row')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(6)
                            ->default(4)
                            ->required(),
                    ])
                    ->columns(4)
                    ->collapsed(),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('scheduled_publish_at')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('scheduled_unpublish_at')
                            ->nullable(),
                        Forms\Components\Toggle::make('auto_publish_products')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Attributes')
                    ->schema([
                        Attributes::make()
                            ->label('Attribute data'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn (Collection $record): string => (string) ($record->translateAttribute('name') ?? 'Unnamed'))
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('group.name')
                    ->label('Group')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('collection_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('auto_assign')
                    ->label('Auto')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('show_on_homepage')
                    ->label('Homepage')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('product_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection_group_id')
                    ->label('Group')
                    ->relationship('group', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('auto_assign')
                    ->label('Auto assign'),

                Tables\Filters\TernaryFilter::make('show_on_homepage')
                    ->label('Homepage'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ProductMetadataRelationManager::class,
            SmartRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'view' => ViewCollection::route('/{record}'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}

