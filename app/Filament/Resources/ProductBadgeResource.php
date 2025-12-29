<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBadgeResource\Pages\CreateProductBadge;
use App\Filament\Resources\ProductBadgeResource\Pages\EditProductBadge;
use App\Filament\Resources\ProductBadgeResource\Pages\ListProductBadges;
use App\Models\ProductBadge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductBadgeResource extends Resource
{
    protected static ?string $model = ProductBadge::class;

    protected static ?string $slug = 'ops-product-badges';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Product Badges';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Badge')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('handle')
                            ->helperText('Optional. If blank, it will be generated from the name.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('type')
                            ->options([
                                'new' => 'New',
                                'sale' => 'Sale',
                                'hot' => 'Hot',
                                'limited' => 'Limited',
                                'exclusive' => 'Exclusive',
                                'custom' => 'Custom',
                            ])
                            ->default('custom')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('label')
                            ->label('Display label')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),

                        Forms\Components\TextInput::make('max_display_count')
                            ->numeric()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Optional. Max number of badges to show per product.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Appearance')
                    ->schema([
                        Forms\Components\TextInput::make('color')
                            ->label('Text color')
                            ->default('#000000')
                            ->maxLength(7),

                        Forms\Components\TextInput::make('background_color')
                            ->label('Background color')
                            ->default('#FFFFFF')
                            ->maxLength(7),

                        Forms\Components\TextInput::make('border_color')
                            ->label('Border color')
                            ->maxLength(7)
                            ->nullable(),

                        Forms\Components\TextInput::make('icon')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Select::make('position')
                            ->options([
                                'top-left' => 'Top left',
                                'top-right' => 'Top right',
                                'bottom-left' => 'Bottom left',
                                'bottom-right' => 'Bottom right',
                                'center' => 'Center',
                            ])
                            ->default('top-left')
                            ->required(),

                        Forms\Components\Select::make('style')
                            ->options([
                                'rounded' => 'Rounded',
                                'square' => 'Square',
                                'pill' => 'Pill',
                                'custom' => 'Custom',
                            ])
                            ->default('rounded')
                            ->required(),

                        Forms\Components\TextInput::make('font_size')
                            ->numeric()
                            ->minValue(8)
                            ->maxValue(24)
                            ->default(12),

                        Forms\Components\TextInput::make('padding_x')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->default(8),

                        Forms\Components\TextInput::make('padding_y')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(20)
                            ->default(4),

                        Forms\Components\TextInput::make('border_radius')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50)
                            ->default(4),

                        Forms\Components\Toggle::make('show_icon')
                            ->default(false),

                        Forms\Components\Toggle::make('animated')
                            ->default(false),

                        Forms\Components\TextInput::make('animation_type')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('E.g. pulse, bounce, flash.'),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Forms\Components\Section::make('Rules (JSON)')
                    ->schema([
                        Forms\Components\Toggle::make('auto_assign')
                            ->default(false)
                            ->helperText('If enabled, automatic badge rules can assign this badge to products.'),

                        Forms\Components\Textarea::make('assignment_rules')
                            ->rows(6)
                            ->helperText('Optional JSON config for auto-assignment rules.')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (string) ($state ?? '')
                            )
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (!is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                return json_decode($state, true);
                            })
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('display_conditions')
                            ->rows(6)
                            ->helperText('Optional JSON config for visibility rules (category/product/search).')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (string) ($state ?? '')
                            )
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (!is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                return json_decode($state, true);
                            })
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsed(),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->nullable(),
                    ])
                    ->columns(2)
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

                Tables\Columns\TextColumn::make('handle')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\IconColumn::make('auto_assign')
                    ->boolean()
                    ->label('Auto')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label('Assignments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'new' => 'New',
                        'sale' => 'Sale',
                        'hot' => 'Hot',
                        'limited' => 'Limited',
                        'exclusive' => 'Exclusive',
                        'custom' => 'Custom',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductBadges::route('/'),
            'create' => CreateProductBadge::route('/create'),
            'edit' => EditProductBadge::route('/{record}/edit'),
        ];
    }
}

