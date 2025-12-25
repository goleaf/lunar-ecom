<?php

namespace App\Filament\Resources\B2BContractResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ContractCreditLimit;

class CreditLimitsRelationManager extends RelationManager
{
    protected static string $relationship = 'creditLimit';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('credit_limit')
                    ->label('Credit Limit')
                    ->numeric()
                    ->required()
                    ->helperText('Credit limit in minor currency units (e.g., cents)'),

                Forms\Components\TextInput::make('current_balance')
                    ->label('Current Balance')
                    ->numeric()
                    ->default(0)
                    ->disabled(),

                Forms\Components\Select::make('payment_terms')
                    ->options([
                        ContractCreditLimit::TERMS_NET_7 => 'Net 7',
                        ContractCreditLimit::TERMS_NET_15 => 'Net 15',
                        ContractCreditLimit::TERMS_NET_30 => 'Net 30',
                        ContractCreditLimit::TERMS_NET_60 => 'Net 60',
                        ContractCreditLimit::TERMS_NET_90 => 'Net 90',
                        ContractCreditLimit::TERMS_IMMEDIATE => 'Immediate',
                    ])
                    ->default(ContractCreditLimit::TERMS_NET_30)
                    ->required(),

                Forms\Components\TextInput::make('payment_terms_days')
                    ->label('Payment Terms (Days)')
                    ->numeric()
                    ->default(30)
                    ->required(),

                Forms\Components\DatePicker::make('last_payment_date')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('credit_limit')
            ->columns([
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Credit Limit')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Current Balance')
                    ->money('USD', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_credit')
                    ->label('Available Credit')
                    ->money('USD', divideBy: 100)
                    ->getStateUsing(fn (ContractCreditLimit $record): int => $record->available_credit)
                    ->color(fn (ContractCreditLimit $record): string => $record->available_credit > 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_terms')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_payment_date')
                    ->date()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

