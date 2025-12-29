<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CheckoutLockResource\Pages\ListCheckoutLocks;
use App\Filament\Resources\CheckoutLockResource\Pages\ViewCheckoutLock;
use App\Models\CheckoutLock;
use App\Services\CheckoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckoutLockResource extends Resource
{
    protected static ?string $model = CheckoutLock::class;

    protected static ?string $slug = 'ops-checkout-locks';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Checkout Locks';

    protected static ?int $navigationSort = 50;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['cart', 'user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lock')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->disabled(),

                        Forms\Components\TextInput::make('state')
                            ->disabled(),

                        Forms\Components\TextInput::make('phase')
                            ->disabled(),

                        Forms\Components\TextInput::make('session_id')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cart_id')
                            ->disabled(),

                        Forms\Components\TextInput::make('user.email')
                            ->label('User email')
                            ->disabled(),

                        Forms\Components\TextInput::make('locked_at')
                            ->disabled(),

                        Forms\Components\TextInput::make('expires_at')
                            ->disabled(),

                        Forms\Components\TextInput::make('completed_at')
                            ->disabled(),

                        Forms\Components\TextInput::make('failed_at')
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Failure / Metadata')
                    ->schema([
                        Forms\Components\Textarea::make('failure_reason')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('metadata')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        CheckoutLock::STATE_COMPLETED => 'success',
                        CheckoutLock::STATE_FAILED => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('phase')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('cart_id')
                    ->label('Cart')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_expired')
                    ->label('Expired')
                    ->state(fn (CheckoutLock $record): bool => $record->isExpired())
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        CheckoutLock::STATE_PENDING => 'Pending',
                        CheckoutLock::STATE_VALIDATING => 'Validating',
                        CheckoutLock::STATE_RESERVING => 'Reserving',
                        CheckoutLock::STATE_LOCKING_PRICES => 'Locking prices',
                        CheckoutLock::STATE_AUTHORIZING => 'Authorizing',
                        CheckoutLock::STATE_CREATING_ORDER => 'Creating order',
                        CheckoutLock::STATE_CAPTURING => 'Capturing',
                        CheckoutLock::STATE_COMMITTING => 'Committing',
                        CheckoutLock::STATE_COMPLETED => 'Completed',
                        CheckoutLock::STATE_FAILED => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-lock-open')
                    ->requiresConfirmation()
                    ->visible(fn (CheckoutLock $record): bool => !$record->isCompleted() && !$record->isFailed())
                    ->action(fn (CheckoutLock $record) => app(CheckoutService::class)->releaseCheckout($record)),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('release')
                    ->label('Release')
                    ->icon('heroicon-o-lock-open')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        /** @var CheckoutLock $record */
                        foreach ($records as $record) {
                            if ($record->isCompleted() || $record->isFailed()) {
                                continue;
                            }

                            app(CheckoutService::class)->releaseCheckout($record);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCheckoutLocks::route('/'),
            'view' => ViewCheckoutLock::route('/{record}'),
        ];
    }
}

