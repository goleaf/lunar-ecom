<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BundleResource\Pages\CreateBundle;
use App\Filament\Resources\BundleResource\Pages\EditBundle;
use App\Filament\Resources\BundleResource\Pages\ListBundles;
use App\Models\Bundle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static ?string $slug = 'ops-bundles';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Bundles';

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'items', 'prices']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bundle')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Bundle product')
                            ->relationship('product', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translate('name') ?? "Product #{$record->id}"
                            ))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->helperText('Optional. If blank, it will be generated from the name.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->nullable(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Select::make('pricing_type')
                            ->options([
                                'fixed' => 'Fixed',
                                'percentage' => 'Percentage',
                                'dynamic' => 'Dynamic',
                            ])
                            ->default('fixed')
                            ->required(),

                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Cents for fixed discount; percent for percentage discount.'),

                        Forms\Components\TextInput::make('bundle_price')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Fixed bundle price in cents (used when pricing type is fixed).'),

                        Forms\Components\Toggle::make('show_individual_prices')
                            ->default(true),

                        Forms\Components\Toggle::make('show_savings')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Inventory')
                    ->schema([
                        Forms\Components\Select::make('inventory_type')
                            ->options([
                                'component' => 'Component (based on item stock)',
                                'independent' => 'Independent (bundle stock field)',
                                'unlimited' => 'Unlimited',
                            ])
                            ->default('component')
                            ->required(),

                        Forms\Components\TextInput::make('stock')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('min_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),

                        Forms\Components\TextInput::make('max_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                    ])
                    ->columns(4)
                    ->collapsed(),

                Forms\Components\Section::make('Display')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_featured')
                            ->default(false),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\Toggle::make('allow_customization')
                            ->default(false),

                        Forms\Components\TextInput::make('image')
                            ->maxLength(255)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Textarea::make('meta_description')
                            ->maxLength(2000)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->minItems(1)
                            ->required()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->relationship('product', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                        $record->translate('name') ?? "Product #{$record->id}"
                                    ))
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('product_variant_id')
                                    ->label('Variant (optional)')
                                    ->relationship('productVariant', 'sku')
                                    ->searchable()
                                    ->nullable(),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                Forms\Components\Toggle::make('is_required')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_default')
                                    ->default(false),

                                Forms\Components\TextInput::make('min_quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),

                                Forms\Components\TextInput::make('max_quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->nullable(),

                                Forms\Components\TextInput::make('price_override')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),

                                Forms\Components\TextInput::make('display_order')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),

                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(2000)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('Price tiers')
                    ->schema([
                        Forms\Components\Repeater::make('prices')
                            ->relationship('prices')
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Select::make('currency_id')
                                    ->relationship('currency', 'code')
                                    ->searchable()
                                    ->required(),

                                Forms\Components\Select::make('customer_group_id')
                                    ->relationship('customerGroup', 'name')
                                    ->searchable()
                                    ->nullable(),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),

                                Forms\Components\TextInput::make('compare_at_price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),

                                Forms\Components\TextInput::make('min_quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1),

                                Forms\Components\TextInput::make('max_quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->nullable(),
                            ])
                            ->columns(3),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('pricing_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inventory_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('pricing_type')
                    ->options([
                        'fixed' => 'Fixed',
                        'percentage' => 'Percentage',
                        'dynamic' => 'Dynamic',
                    ]),
                Tables\Filters\SelectFilter::make('inventory_type')
                    ->options([
                        'component' => 'Component',
                        'independent' => 'Independent',
                        'unlimited' => 'Unlimited',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('display_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBundles::route('/'),
            'create' => CreateBundle::route('/create'),
            'edit' => EditBundle::route('/{record}/edit'),
        ];
    }
}

