<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityBookingResource\Pages\ListAvailabilityBookings;
use App\Filament\Resources\AvailabilityBookingResource\Pages\ViewAvailabilityBooking;
use App\Models\AvailabilityBooking;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AvailabilityBookingResource extends Resource
{
    protected static ?string $model = AvailabilityBooking::class;

    protected static ?string $slug = 'ops-availability-bookings';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?string $navigationLabel = 'Availability Bookings';

    protected static ?int $navigationSort = 77;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'productVariant', 'customer', 'order']);
    }

    public static function form(Form $form): Form
    {
        // Read-only for now.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->getStateUsing(fn (AvailabilityBooking $record): string => (string) (
                        $record->product?->translateAttribute('name') ?? "Product #{$record->product_id}"
                    ))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('variant_sku')
                    ->label('Variant')
                    ->getStateUsing(fn (AvailabilityBooking $record): string => $record->productVariant?->sku ?? ($record->product_variant_id ? "#{$record->product_variant_id}" : 'N/A'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('customer_email')
                    ->label('Customer')
                    ->getStateUsing(fn (AvailabilityBooking $record): string => $record->customer_email ?? $record->customer?->email ?? 'N/A')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('currency_code')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'no_show' => 'No show',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->requiresConfirmation()
                    ->visible(fn (AvailabilityBooking $record): bool => in_array($record->status, ['pending'], true))
                    ->action(fn (AvailabilityBooking $record) => $record->update(['status' => 'confirmed'])),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->requiresConfirmation()
                    ->visible(fn (AvailabilityBooking $record): bool => in_array($record->status, ['pending', 'confirmed'], true))
                    ->action(fn (AvailabilityBooking $record) => $record->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                    ])),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAvailabilityBookings::route('/'),
            'view' => ViewAvailabilityBooking::route('/{record}'),
        ];
    }
}

