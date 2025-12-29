<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBadgeRuleResource\Pages;
use App\Models\ProductBadgeRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductBadgeRuleResource extends Resource
{
    protected static ?string $model = ProductBadgeRule::class;

    protected static ?string $slug = 'ops-product-badge-rules';

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Badge Rules';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['badge']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule')
                    ->schema([
                        Forms\Components\Select::make('badge_id')
                            ->relationship('badge', 'name')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('condition_type')
                            ->options([
                                'manual' => 'Manual',
                                'automatic' => 'Automatic',
                            ])
                            ->default('manual')
                            ->required(),

                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(2000)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Conditions (JSON)')
                    ->schema([
                        Forms\Components\Textarea::make('conditions')
                            ->required()
                            ->rows(10)
                            ->helperText('JSON map of conditions. Example: {"is_new":{"enabled":true,"days":30}}')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (string) ($state ?? '')
                            )
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (!is_string($state) || trim($state) === '') {
                                    return [];
                                }

                                return json_decode($state, true) ?? [];
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('expires_at')
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
                Tables\Columns\TextColumn::make('badge.name')
                    ->label('Badge')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('condition_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
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
                Tables\Filters\SelectFilter::make('condition_type')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automatic',
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
            'index' => Pages\ListProductBadgeRules::route('/'),
            'create' => Pages\CreateProductBadgeRule::route('/create'),
            'edit' => Pages\EditProductBadgeRule::route('/{record}/edit'),
        ];
    }
}

