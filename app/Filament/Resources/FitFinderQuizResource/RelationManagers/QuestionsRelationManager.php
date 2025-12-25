<?php

namespace App\Filament\Resources\FitFinderQuizResource\RelationManagers;

use App\Models\FitFinderAnswer;
use App\Models\FitFinderQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('question_text')
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('manage_answers')
                    ->label('Manage Answers')
                    ->icon('heroicon-o-list-bullet')
                    ->url(fn (FitFinderQuestion $record): string => 
                        \App\Filament\Resources\FitFinderQuestionResource::getUrl('edit', ['record' => $record])
                    ),
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
}

