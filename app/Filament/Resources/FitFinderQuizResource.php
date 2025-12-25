<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FitFinderQuizResource\Pages;
use App\Filament\Resources\FitFinderQuizResource\RelationManagers\QuestionsRelationManager;
use App\Models\FitFinderQuiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FitFinderQuizResource extends Resource
{
    protected static ?string $model = FitFinderQuiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category_type')
                            ->options([
                                'clothing' => 'Clothing',
                                'shoes' => 'Shoes',
                                'accessories' => 'Accessories',
                                'jewelry' => 'Jewelry',
                                'bags' => 'Bags',
                                'other' => 'Other',
                            ])
                            ->required(),

                        Forms\Components\Select::make('gender')
                            ->options([
                                'men' => 'Men',
                                'women' => 'Women',
                                'unisex' => 'Unisex',
                                'kids' => 'Kids',
                            ])
                            ->nullable(),

                        Forms\Components\Select::make('size_guide_id')
                            ->label('Associated Size Guide')
                            ->relationship('sizeGuide', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recommendation Logic')
                    ->description('Define rules for size recommendations based on quiz answers. Use JSON format.')
                    ->schema([
                        Forms\Components\Textarea::make('recommendation_logic')
                            ->label('Recommendation Logic (JSON)')
                            ->helperText('Enter recommendation logic in JSON format. Example: [{"conditions": [{"question_id": 1, "answer_id": 2}], "recommended_size": "M"}]')
                            ->rows(10)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '')
                            ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make('Associated Products')
                    ->schema([
                        Forms\Components\Select::make('products')
                            ->relationship('products', 'translate.name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clothing' => 'success',
                        'shoes' => 'info',
                        'accessories' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sizeGuide.name')
                    ->label('Size Guide')
                    ->sortable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Questions')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_type')
                    ->options([
                        'clothing' => 'Clothing',
                        'shoes' => 'Shoes',
                        'accessories' => 'Accessories',
                        'jewelry' => 'Jewelry',
                        'bags' => 'Bags',
                        'other' => 'Other',
                    ]),

                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'men' => 'Men',
                        'women' => 'Women',
                        'unisex' => 'Unisex',
                        'kids' => 'Kids',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFitFinderQuizzes::route('/'),
            'create' => Pages\CreateFitFinderQuiz::route('/create'),
            'edit' => Pages\EditFitFinderQuiz::route('/{record}/edit'),
        ];
    }
}

