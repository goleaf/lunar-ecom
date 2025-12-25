<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCodeManagementResource\Pages;
use App\Models\User;
use App\Services\ReferralCodeGeneratorService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ReferralCodeManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Referral Codes';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Referral Code';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Referral Code')
                    ->schema([
                        Forms\Components\TextInput::make('referral_code')
                            ->label('Current Code')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('referral_link_slug')
                            ->label('Link Slug')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('referral_link')
                            ->label('Referral Link')
                            ->disabled()
                            ->default(fn ($record) => $record ? $record->getReferralLink() : null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Referral Status')
                    ->schema([
                        Forms\Components\TextInput::make('referred_by.name')
                            ->label('Referred By')
                            ->disabled(),

                        Forms\Components\TextInput::make('referred_at')
                            ->label('Referred At')
                            ->disabled()
                            ->dateTime(),

                        Forms\Components\Toggle::make('referral_blocked')
                            ->label('Block Referral Rewards')
                            ->helperText('Prevent this user from earning referral rewards'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Code copied!'),

                Tables\Columns\TextColumn::make('referral_link_slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referred_by.name')
                    ->label('Referred By')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('referees_count')
                    ->counts('referees')
                    ->label('Referrals')
                    ->sortable(),

                Tables\Columns\IconColumn::make('referral_blocked')
                    ->boolean()
                    ->label('Blocked')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_code')
                    ->label('Has Referral Code')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('referral_code')),

                Tables\Filters\Filter::make('no_code')
                    ->label('No Referral Code')
                    ->query(fn (Builder $query): Builder => $query->whereNull('referral_code')),

                Tables\Filters\TernaryFilter::make('referral_blocked')
                    ->label('Blocked'),

                Tables\Filters\Filter::make('referred_by')
                    ->form([
                        Forms\Components\Select::make('referrer_id')
                            ->label('Referred By')
                            ->relationship('referrer', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['referrer_id'],
                            fn (Builder $query, $referrerId): Builder => $query->where('referred_by_user_id', $referrerId),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('regenerate_code')
                    ->label('Regenerate Code')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Referral Code')
                    ->modalDescription('This will generate a new referral code for this user. The old code will no longer work.')
                    ->action(function (User $record) {
                        $generator = app(ReferralCodeGeneratorService::class);
                        $newCode = $generator->regenerateForUser($record);

                        Notification::make()
                            ->title('Referral Code Regenerated')
                            ->success()
                            ->body("New code: {$newCode}")
                            ->send();
                    }),

                Tables\Actions\Action::make('generate_code')
                    ->label('Generate Code')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->referral_code)
                    ->action(function (User $record) {
                        $record->generateReferralCode();

                        Notification::make()
                            ->title('Referral Code Generated')
                            ->success()
                            ->body("Code: {$record->referral_code}")
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('generate_codes')
                        ->label('Generate Codes')
                        ->icon('heroicon-o-plus-circle')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $generator = app(ReferralCodeGeneratorService::class);
                            $count = 0;

                            foreach ($records as $user) {
                                if (!$user->referral_code) {
                                    $user->generateReferralCode();
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title('Codes Generated')
                                ->success()
                                ->body("Generated {$count} referral codes")
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('block_referrals')
                        ->label('Block Referral Rewards')
                        ->icon('heroicon-o-ban')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            User::whereIn('id', $records->pluck('id'))
                                ->update(['referral_blocked' => true]);

                            Notification::make()
                                ->title('Referrals Blocked')
                                ->success()
                                ->body('Selected users can no longer earn referral rewards')
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('unblock_referrals')
                        ->label('Unblock Referral Rewards')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            User::whereIn('id', $records->pluck('id'))
                                ->update(['referral_blocked' => false]);

                            Notification::make()
                                ->title('Referrals Unblocked')
                                ->success()
                                ->body('Selected users can now earn referral rewards')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReferralCodes::route('/'),
            'view' => Pages\ViewReferralCode::route('/{record}'),
            'edit' => Pages\EditReferralCode::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('email'); // Only show users with emails
    }
}

