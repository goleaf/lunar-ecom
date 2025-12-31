<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralAttributionResource\Pages;
use App\Models\ReferralAttribution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralAttributionResource extends Resource
{
    protected static ?string $model = ReferralAttribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attribution Details')
                    ->schema([
                        Forms\Components\Select::make('referee_user_id')
                            ->relationship('referee', 'email')
                            ->searchable(['email', 'name'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('referrer_user_id')
                            ->relationship('referrer', 'email')
                            ->searchable(['email', 'name'])
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('program_id')
                            ->relationship('program', 'name')
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
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referee.email')
                    ->label('Referee')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referrer.email')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code_used')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('attribution_method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReferralAttribution::METHOD_CODE => 'success',
                        ReferralAttribution::METHOD_LINK => 'info',
                        ReferralAttribution::METHOD_MANUAL_ADMIN => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        2 => 'info',
                        3 => 'warning',
                        default => 'gray',
                    }),

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

                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('fraud_flags')
                    ->label('Fraud Flags')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->getStateUsing(fn (ReferralAttribution $record): bool => static::hasFraudFlags($record))
                    ->tooltip(fn (ReferralAttribution $record): string => static::getFraudFlagsTooltip($record)),
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

                Tables\Filters\SelectFilter::make('program_id')
                    ->relationship('program', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ReferralAttribution $record): bool => $record->status === ReferralAttribution::STATUS_PENDING)
                    ->action(function (ReferralAttribution $record) {
                        $record->confirm();
                    }),

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
                    ->action(function (ReferralAttribution $record, array $data) {
                        $record->reject($data['reason']);
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('confirm')
                        ->label('Confirm Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            ReferralAttribution::whereIn('id', $records->pluck('id'))
                                ->where('status', ReferralAttribution::STATUS_PENDING)
                                ->update(['status' => ReferralAttribution::STATUS_CONFIRMED]);
                        }),

                    Tables\Actions\BulkAction::make('reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Rejection Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->action(function ($records, array $data) {
                            ReferralAttribution::whereIn('id', $records->pluck('id'))
                                ->where('status', ReferralAttribution::STATUS_PENDING)
                                ->update([
                                    'status' => ReferralAttribution::STATUS_REJECTED,
                                    'rejection_reason' => $data['reason'],
                                ]);
                        }),
                ]),
            ])
            ->defaultSort('attributed_at', 'desc');

        return $table;
    }

    protected static function hasFraudFlags(ReferralAttribution $record): bool
    {
        // Check for fraud flags
        $referee = $record->referee;
        $referrer = $record->referrer;

        // Same IP check
        $refereeIp = hash('sha256', request()->ip());
        $referrerRecentOrders = \Lunar\Models\Order::whereHas('customer', function ($q) use ($referrer) {
            $q->where('user_id', $referrer->id);
        })->get();

        foreach ($referrerRecentOrders as $order) {
            $orderIp = hash('sha256', $order->meta['ip_address'] ?? '');
            if ($orderIp === $refereeIp) {
                return true;
            }
        }

        // Same email domain
        if ($referee->email && $referrer->email) {
            $refereeDomain = substr(strrchr($referee->email, "@"), 1);
            $referrerDomain = substr(strrchr($referrer->email, "@"), 1);
            if ($refereeDomain === $referrerDomain && $refereeDomain !== 'gmail.com') {
                return true;
            }
        }

        return false;
    }

    protected static function getFraudFlagsTooltip(ReferralAttribution $record): string
    {
        $flags = [];

        $referee = $record->referee;
        $referrer = $record->referrer;

        // Check same IP
        $refereeIp = hash('sha256', request()->ip());
        $referrerRecentOrders = \Lunar\Models\Order::whereHas('customer', function ($q) use ($referrer) {
            $q->where('user_id', $referrer->id);
        })->get();

        foreach ($referrerRecentOrders as $order) {
            $orderIp = hash('sha256', $order->meta['ip_address'] ?? '');
            if ($orderIp === $refereeIp) {
                $flags[] = 'Same IP address';
                break;
            }
        }

        // Check same email domain
        if ($referee->email && $referrer->email) {
            $refereeDomain = substr(strrchr($referee->email, "@"), 1);
            $referrerDomain = substr(strrchr($referrer->email, "@"), 1);
            if ($refereeDomain === $referrerDomain && $refereeDomain !== 'gmail.com') {
                $flags[] = 'Same email domain';
            }
        }

        return empty($flags) ? 'No fraud flags detected' : implode(', ', $flags);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralAttributions::route('/'),
            'create' => Pages\CreateReferralAttribution::route('/create'),
            'view' => Pages\ViewReferralAttribution::route('/{record}'),
            'edit' => Pages\EditReferralAttribution::route('/{record}/edit'),
        ];
    }
}

