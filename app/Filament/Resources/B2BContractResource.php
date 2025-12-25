<?php

namespace App\Filament\Resources;

use App\Filament\Resources\B2BContractResource\Pages;
use App\Filament\Resources\B2BContractResource\RelationManagers;
use App\Models\B2BContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\Customer;
use Lunar\Models\Currency;

class B2BContractResource extends Resource
{
    protected static ?string $model = B2BContract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'B2B Management';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contract Information')
                    ->schema([
                        Forms\Components\TextInput::make('contract_id')
                            ->label('Contract ID')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique contract identifier'),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer / Company')
                            ->relationship('customer', 'company_name')
                            ->searchable(['company_name', 'first_name', 'last_name'])
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->company_name ?: "{$record->first_name} {$record->last_name}")
                            ->required()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('priority')
                            ->numeric()
                            ->default(0)
                            ->helperText('Higher priority contracts are applied first'),

                        Forms\Components\TextInput::make('terms_reference')
                            ->label('Terms Reference')
                            ->maxLength(255)
                            ->helperText('Reference to terms document'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validity Period')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->required()
                            ->default(now()),

                        Forms\Components\DatePicker::make('valid_to')
                            ->nullable()
                            ->helperText('Leave empty for contracts without expiry'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Approval')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                B2BContract::STATUS_DRAFT => 'Draft',
                                B2BContract::STATUS_PENDING_APPROVAL => 'Pending Approval',
                                B2BContract::STATUS_ACTIVE => 'Active',
                                B2BContract::STATUS_EXPIRED => 'Expired',
                                B2BContract::STATUS_CANCELLED => 'Cancelled',
                            ])
                            ->default(B2BContract::STATUS_DRAFT)
                            ->required(),

                        Forms\Components\Select::make('approval_state')
                            ->options([
                                B2BContract::APPROVAL_PENDING => 'Pending',
                                B2BContract::APPROVAL_APPROVED => 'Approved',
                                B2BContract::APPROVAL_REJECTED => 'Rejected',
                            ])
                            ->default(B2BContract::APPROVAL_PENDING)
                            ->required(),

                        Forms\Components\Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($record) => $record && $record->approved_at !== null),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->disabled(),

                        Forms\Components\Textarea::make('approval_notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('meta')
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
                Tables\Columns\TextColumn::make('contract_id')
                    ->label('Contract ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.company_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        B2BContract::STATUS_ACTIVE => 'success',
                        B2BContract::STATUS_PENDING_APPROVAL => 'warning',
                        B2BContract::STATUS_DRAFT => 'gray',
                        B2BContract::STATUS_EXPIRED => 'danger',
                        B2BContract::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('approval_state')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        B2BContract::APPROVAL_APPROVED => 'success',
                        B2BContract::APPROVAL_PENDING => 'warning',
                        B2BContract::APPROVAL_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_to')
                    ->date()
                    ->sortable()
                    ->placeholder('No expiry'),

                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_lists_count')
                    ->counts('priceLists')
                    ->label('Price Lists')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        B2BContract::STATUS_DRAFT => 'Draft',
                        B2BContract::STATUS_PENDING_APPROVAL => 'Pending Approval',
                        B2BContract::STATUS_ACTIVE => 'Active',
                        B2BContract::STATUS_EXPIRED => 'Expired',
                        B2BContract::STATUS_CANCELLED => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('approval_state')
                    ->options([
                        B2BContract::APPROVAL_PENDING => 'Pending',
                        B2BContract::APPROVAL_APPROVED => 'Approved',
                        B2BContract::APPROVAL_REJECTED => 'Rejected',
                    ]),

                Tables\Filters\Filter::make('valid_from')
                    ->form([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Valid From'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valid_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('valid_from', '>=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query): Builder => $query->where('valid_to', '>=', now())
                        ->where('valid_to', '<=', now()->addDays(30))
                        ->where('status', B2BContract::STATUS_ACTIVE)),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (B2BContract $record): bool => $record->approval_state === B2BContract::APPROVAL_PENDING)
                    ->action(function (B2BContract $record) {
                        $record->approve(auth()->user());
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Rejection Notes')
                            ->required(),
                    ])
                    ->visible(fn (B2BContract $record): bool => $record->approval_state === B2BContract::APPROVAL_PENDING)
                    ->action(function (B2BContract $record, array $data) {
                        $record->reject(auth()->user(), $data['notes']);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceListsRelationManager::class,
            RelationManagers\RulesRelationManager::class,
            RelationManagers\CreditLimitsRelationManager::class,
            RelationManagers\SalesRepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2BContracts::route('/'),
            'create' => Pages\CreateB2BContract::route('/create'),
            'view' => Pages\ViewB2BContract::route('/{record}'),
            'edit' => Pages\EditB2BContract::route('/{record}/edit'),
        ];
    }
}

