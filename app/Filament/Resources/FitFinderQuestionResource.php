<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FitFinderQuestionResource\Pages;
use App\Filament\Resources\FitFinderQuestionResource\RelationManagers\AnswersRelationManager;
use App\Models\FitFinderQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FitFinderQuestionResource extends Resource
{
    protected static ?string $model = FitFinderQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Product Management';

    protected static ?int $navigationSort = 33;

    protected static ?string $navigationLabel = 'Fit Finder Questions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Question Information')
                    ->schema([
                        Forms\Components\Select::make('fit_finder_quiz_id')
                            ->relationship('quiz', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Textarea::make('question_text')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('question_type')
                            ->options([
                                'single_choice' => 'Single Choice',
                                'multiple_choice' => 'Multiple Choice',
                                'text' => 'Text Input',
                                'number' => 'Number Input',
                            ])
                            ->default('single_choice')
                            ->required(),

                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Forms\Components\Toggle::make('is_required')
                            ->default(true)
                            ->required(),

                        Forms\Components\Textarea::make('help_text')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quiz.name')
                    ->label('Quiz')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_text')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('question_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('answers_count')
                    ->counts('answers')
                    ->label('Answers')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_required')
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
                Tables\Filters\SelectFilter::make('fit_finder_quiz_id')
                    ->relationship('quiz', 'name')
                    ->label('Quiz'),

                Tables\Filters\SelectFilter::make('question_type')
                    ->options([
                        'single_choice' => 'Single Choice',
                        'multiple_choice' => 'Multiple Choice',
                        'text' => 'Text Input',
                        'number' => 'Number Input',
                    ]),
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
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFitFinderQuestions::route('/'),
            'create' => Pages\CreateFitFinderQuestion::route('/create'),
            'edit' => Pages\EditFitFinderQuestion::route('/{record}/edit'),
        ];
    }
}

