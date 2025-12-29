<?php

namespace App\Filament\Resources\ProductQuestionResource\RelationManagers;

use App\Models\ProductAnswer;
use App\Services\QuestionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'allAnswers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('answer')
                    ->required()
                    ->minLength(10)
                    ->maxLength(2000)
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_official')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('answer')
            ->columns([
                Tables\Columns\TextColumn::make('answer')
                    ->label('Answer')
                    ->limit(70),

                Tables\Columns\TextColumn::make('answerer_type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_official')
                    ->boolean()
                    ->label('Official')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('answered_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add answer'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProductAnswer $record): bool => $record->status !== 'approved' || ! $record->is_approved)
                    ->requiresConfirmation()
                    ->action(fn (ProductAnswer $record) => app(QuestionService::class)->moderateAnswer($record, 'approved')),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ProductAnswer $record): bool => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(fn (ProductAnswer $record) => app(QuestionService::class)->moderateAnswer($record, 'rejected')),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('answered_at', 'desc');
    }
}

