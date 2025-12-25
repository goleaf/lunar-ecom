<?php

namespace App\Filament\Resources\ReferralProgramResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ReferralReward;

class RewardsRelationManager extends RelationManager
{
    protected static string $relationship = 'rewards';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('reward_type')
                    ->options([
                        ReferralReward::TYPE_DISCOUNT_CODE => 'Discount Code',
                        ReferralReward::TYPE_CREDIT => 'Credit',
                        ReferralReward::TYPE_PERCENTAGE => 'Percentage',
                        ReferralReward::TYPE_FIXED_AMOUNT => 'Fixed Amount',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('reward_value')
                    ->numeric()
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        ReferralReward::STATUS_PENDING => 'Pending',
                        ReferralReward::STATUS_ISSUED => 'Issued',
                        ReferralReward::STATUS_REDEEMED => 'Redeemed',
                        ReferralReward::STATUS_EXPIRED => 'Expired',
                        ReferralReward::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reward_type')
            ->columns([
                Tables\Columns\TextColumn::make('reward_type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reward_value')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralReward::STATUS_ISSUED => 'success',
                        ReferralReward::STATUS_PENDING => 'warning',
                        ReferralReward::STATUS_REDEEMED => 'info',
                        ReferralReward::STATUS_EXPIRED => 'gray',
                        ReferralReward::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_code')
                    ->label('Discount Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('times_used')
                    ->label('Times Used')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_uses')
                    ->label('Max Uses')
                    ->sortable()
                    ->placeholder('∞'),

                Tables\Columns\TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('No expiry'),

                Tables\Columns\TextColumn::make('redeemed_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not redeemed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        ReferralReward::STATUS_PENDING => 'Pending',
                        ReferralReward::STATUS_ISSUED => 'Issued',
                        ReferralReward::STATUS_REDEEMED => 'Redeemed',
                        ReferralReward::STATUS_EXPIRED => 'Expired',
                        ReferralReward::STATUS_CANCELLED => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('reward_type')
                    ->options([
                        ReferralReward::TYPE_DISCOUNT_CODE => 'Discount Code',
                        ReferralReward::TYPE_CREDIT => 'Credit',
                        ReferralReward::TYPE_PERCENTAGE => 'Percentage',
                        ReferralReward::TYPE_FIXED_AMOUNT => 'Fixed Amount',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

