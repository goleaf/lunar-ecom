<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecommendationRuleResource\Pages;
use App\Models\RecommendationRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RecommendationRuleResource extends Resource
{
    protected static ?string $model = RecommendationRule::class;

    protected static ?string $slug = 'ops-recommendation-rules';

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Recommendation Rules';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Rule')
                    ->schema([
                        Forms\Components\Select::make('source_product_id')
                            ->label('Source product (optional)')
                            ->relationship('sourceProduct', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translate('name') ?? "Product #{$record->id}"
                            ))
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('recommended_product_id')
                            ->label('Recommended product')
                            ->relationship('recommendedProduct', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translate('name') ?? "Product #{$record->id}"
                            ))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('rule_type')
                            ->label('Rule type')
                            ->default('manual')
                            ->required()
                            ->maxLength(50)
                            ->helperText('Examples: manual, category, attribute, cross_sell, upsell, similar.'),

                        Forms\Components\TextInput::make('name')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->nullable(),

                        Forms\Components\Textarea::make('conditions')
                            ->rows(6)
                            ->helperText('Optional JSON config (e.g. categories, tags, attributes).')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? (string) json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                                : (string) ($state ?? '')
                            )
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (! is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);
                                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                            })
                            ->nullable()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\TextInput::make('ab_test_variant')
                            ->label('A/B test variant')
                            ->maxLength(50)
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metrics (read-only)')
                    ->schema([
                        Forms\Components\TextInput::make('display_count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('click_count')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('conversion_rate')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state): string {
                                if ($state === null) {
                                    return '0%';
                                }

                                $rate = (float) $state;
                                return rtrim(rtrim(number_format($rate * 100, 2, '.', ''), '0'), '.') . '%';
                            }),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Rule')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rule_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('source_product_name')
                    ->label('Source')
                    ->getStateUsing(fn (RecommendationRule $record): string => (string) (
                        $record->sourceProduct?->translate('name') ?? 'Any'
                    ))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('recommended_product_name')
                    ->label('Recommended')
                    ->getStateUsing(fn (RecommendationRule $record): string => (string) (
                        $record->recommendedProduct?->translate('name') ?? "Product #{$record->recommended_product_id}"
                    ))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('click_count')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('conversion_rate')
                    ->label('CR')
                    ->formatStateUsing(function ($state): string {
                        if ($state === null) {
                            return '0%';
                        }

                        $rate = (float) $state;
                        return rtrim(rtrim(number_format($rate * 100, 2, '.', ''), '0'), '.') . '%';
                    })
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

                Tables\Filters\SelectFilter::make('rule_type')
                    ->options([
                        'manual' => 'Manual',
                        'category' => 'Category',
                        'attribute' => 'Attribute',
                        'cross_sell' => 'Cross-sell',
                        'upsell' => 'Upsell',
                        'similar' => 'Similar',
                        'complementary' => 'Complementary',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('resetMetrics')
                    ->label('Reset metrics')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (RecommendationRule $record): bool => (int) $record->display_count > 0 || (int) $record->click_count > 0)
                    ->action(function (RecommendationRule $record): void {
                        $record->update([
                            'display_count' => 0,
                            'click_count' => 0,
                            'conversion_rate' => 0,
                        ]);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendationRules::route('/'),
            'create' => Pages\CreateRecommendationRule::route('/create'),
            'view' => Pages\ViewRecommendationRule::route('/{record}'),
            'edit' => Pages\EditRecommendationRule::route('/{record}/edit'),
        ];
    }
}

