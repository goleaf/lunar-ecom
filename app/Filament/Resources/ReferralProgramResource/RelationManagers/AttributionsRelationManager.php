<?php

namespace App\Filament\Resources\ReferralProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ReferralAttribution;

class AttributionsRelationManager extends RelationManager
{
    protected static string $relationship = 'attributions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('referee_user_id')
                    ->relationship('referee', 'email')
                    ->searchable()
                    ->required()
                    ->preload(),

                Forms\Components\Select::make('referrer_user_id')
                    ->relationship('referrer', 'email')
                    ->searchable()
                    ->required()
                    ->preload(),

                Forms\Components\TextInput::make('code_used')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('attribution_method')
                    ->options([
                        ReferralAttribution::METHOD_CODE => 'Explicit Code',
                        ReferralAttribution::METHOD_LINK => 'Referral Link',
                        ReferralAttribution::METHOD_MANUAL_ADMIN => 'Manual Admin',
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        ReferralAttribution::STATUS_PENDING => 'Pending',
                        ReferralAttribution::STATUS_CONFIRMED => 'Confirmed',
                        ReferralAttribution::STATUS_REJECTED => 'Rejected',
                    ])
                    ->required(),

                Forms\Components\Textarea::make('rejection_reason')
                    ->rows(3)
                    ->visible(fn ($get) => $get('status') === ReferralAttribution::STATUS_REJECTED),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code_used')
            ->columns([
                Tables\Columns\TextColumn::make('referee.email')
                    ->label('Referee')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer.email')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code_used')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attribution_method')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralAttribution::STATUS_CONFIRMED => 'success',
                        ReferralAttribution::STATUS_PENDING => 'warning',
                        ReferralAttribution::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('attributed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReferralAttribution::STATUS_PENDING => 'Pending',
                        ReferralAttribution::STATUS_CONFIRMED => 'Confirmed',
                        ReferralAttribution::STATUS_REJECTED => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('attribution_method')
                    ->options([
                        ReferralAttribution::METHOD_CODE => 'Explicit Code',
                        ReferralAttribution::METHOD_LINK => 'Referral Link',
                        ReferralAttribution::METHOD_MANUAL_ADMIN => 'Manual Admin',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ReferralAttribution $record): bool => $record->status === ReferralAttribution::STATUS_PENDING)
                    ->action(fn (ReferralAttribution $record) => $record->confirm()),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (ReferralAttribution $record): bool => $record->status === ReferralAttribution::STATUS_PENDING)
                    ->action(fn (ReferralAttribution $record, array $data) => $record->reject($data['reason'])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('attributed_at', 'desc');
    }
}

