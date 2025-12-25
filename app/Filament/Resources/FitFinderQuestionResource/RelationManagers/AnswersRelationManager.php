<?php

namespace App\Filament\Resources\FitFinderQuestionResource\RelationManagers;

use App\Models\FitFinderAnswer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('answer_text')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('answer_value')
                    ->label('Answer Value')
                    ->helperText('Value used in recommendation logic')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\TextInput::make('display_order')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\Textarea::make('size_adjustment')
                    ->label('Size Adjustment (JSON)')
                    ->helperText('Size adjustments based on this answer. Example: {"adjustment": "+1", "reason": "prefers loose fit"}')
                    ->rows(3)
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT) : '')
                    ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('answer_text')
            ->columns([
                Tables\Columns\TextColumn::make('answer_text')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('answer_value')
                    ->label('Value')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('display_order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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

