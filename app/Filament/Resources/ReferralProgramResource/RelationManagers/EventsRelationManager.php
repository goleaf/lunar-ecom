<?php

namespace App\Filament\Resources\ReferralProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ReferralEvent;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('event_type')
                    ->options([
                        ReferralEvent::EVENT_SIGNUP => 'Signup',
                        ReferralEvent::EVENT_FIRST_PURCHASE => 'First Purchase',
                        ReferralEvent::EVENT_REPEAT_PURCHASE => 'Repeat Purchase',
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        ReferralEvent::STATUS_PENDING => 'Pending',
                        ReferralEvent::STATUS_PROCESSED => 'Processed',
                        ReferralEvent::STATUS_FAILED => 'Failed',
                        ReferralEvent::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_type')
            ->columns([
                Tables\Columns\TextColumn::make('event_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralEvent::EVENT_SIGNUP => 'success',
                        ReferralEvent::EVENT_FIRST_PURCHASE => 'info',
                        ReferralEvent::EVENT_REPEAT_PURCHASE => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralEvent::STATUS_PROCESSED => 'success',
                        ReferralEvent::STATUS_PENDING => 'warning',
                        ReferralEvent::STATUS_FAILED => 'danger',
                        ReferralEvent::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referee.name')
                    ->label('Referee')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reward_value')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not processed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        ReferralEvent::EVENT_SIGNUP => 'Signup',
                        ReferralEvent::EVENT_FIRST_PURCHASE => 'First Purchase',
                        ReferralEvent::EVENT_REPEAT_PURCHASE => 'Repeat Purchase',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReferralEvent::STATUS_PENDING => 'Pending',
                        ReferralEvent::STATUS_PROCESSED => 'Processed',
                        ReferralEvent::STATUS_FAILED => 'Failed',
                        ReferralEvent::STATUS_CANCELLED => 'Cancelled',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

