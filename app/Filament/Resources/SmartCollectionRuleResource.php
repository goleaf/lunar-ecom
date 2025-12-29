<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmartCollectionRuleResource\Pages\CreateSmartCollectionRule;
use App\Filament\Resources\SmartCollectionRuleResource\Pages\EditSmartCollectionRule;
use App\Filament\Resources\SmartCollectionRuleResource\Pages\ListSmartCollectionRules;
use App\Filament\Resources\SmartCollectionRuleResource\Pages\ViewSmartCollectionRule;
use App\Models\SmartCollectionRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SmartCollectionRuleResource extends Resource
{
    protected static ?string $model = SmartCollectionRule::class;

    protected static ?string $slug = 'ops-smart-collection-rules';

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Merchandising';

    protected static ?string $navigationLabel = 'Smart Collection Rules';

    protected static ?int $navigationSort = 70;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['collection']);
    }

    public static function form(Form $form): Form
    {
        $fieldOptions = collect(SmartCollectionRule::getAvailableFields())
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label'] ?? $key])
            ->all();

        $operatorOptions = SmartCollectionRule::getAvailableOperators();

        return $form
            ->schema([
                Forms\Components\Section::make('Rule')
                    ->schema([
                        Forms\Components\Select::make('collection_id')
                            ->relationship('collection', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) (
                                $record->translateAttribute('name') ?? $record->name ?? "Collection #{$record->id}"
                            ))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('field')
                            ->options($fieldOptions)
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('operator')
                            ->options($operatorOptions)
                            ->required(),

                        Forms\Components\Textarea::make('value')
                            ->label('Value (JSON or scalar)')
                            ->helperText('Enter a scalar (e.g. "summer") or JSON (e.g. {"min":10,"max":50}).')
                            ->rows(3)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) ($state ?? ''))
                            ->dehydrateStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return $state;
                                }

                                if (! is_string($state) || trim($state) === '') {
                                    return null;
                                }

                                $decoded = json_decode($state, true);
                                return json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                            })
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Logic')
                    ->schema([
                        Forms\Components\TextInput::make('logic_group')
                            ->maxLength(100)
                            ->nullable()
                            ->helperText('Optional group key for combining multiple rules.'),

                        Forms\Components\Select::make('group_operator')
                            ->options([
                                'and' => 'AND',
                                'or' => 'OR',
                            ])
                            ->default('and')
                            ->required(),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),

                        Forms\Components\Textarea::make('description')
                            ->rows(2)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $fieldOptions = collect(SmartCollectionRule::getAvailableFields())
            ->mapWithKeys(fn (array $meta, string $key) => [$key => $meta['label'] ?? $key])
            ->all();

        $operatorOptions = SmartCollectionRule::getAvailableOperators();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('collection_name')
                    ->label('Collection')
                    ->getStateUsing(fn (SmartCollectionRule $record): string => (string) (
                        $record->collection?->translateAttribute('name') ?? $record->collection?->name ?? "Collection #{$record->collection_id}"
                    ))
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('field')
                    ->formatStateUsing(fn (?string $state) => $fieldOptions[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('operator')
                    ->formatStateUsing(fn (?string $state) => $operatorOptions[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('collection_id')
                    ->relationship('collection', 'id')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('field')
                    ->options($fieldOptions),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('priority');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSmartCollectionRules::route('/'),
            'create' => CreateSmartCollectionRule::route('/create'),
            'view' => ViewSmartCollectionRule::route('/{record}'),
            'edit' => EditSmartCollectionRule::route('/{record}/edit'),
        ];
    }
}

