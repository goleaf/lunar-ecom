<?php

namespace App\Filament\Resources\ReferralProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\ReferralCode;

class ReferralCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'referralCodes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('URL-friendly identifier'),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),

                Forms\Components\DateTimePicker::make('expires_at')
                    ->nullable(),

                Forms\Components\TextInput::make('max_uses')
                    ->numeric()
                    ->helperText('Leave empty for unlimited'),

                Forms\Components\TextInput::make('custom_url')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Custom tracking URL'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_uses')
                    ->label('Uses')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_uses')
                    ->label('Max Uses')
                    ->sortable()
                    ->placeholder('∞'),

                Tables\Columns\TextColumn::make('total_clicks')
                    ->label('Clicks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_signups')
                    ->label('Signups')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_purchases')
                    ->label('Purchases')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No expiry'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard')
                    ->action(function (ReferralCode $record) {
                        return $record->getReferralUrl();
                    })
                    ->requiresConfirmation(false),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}


