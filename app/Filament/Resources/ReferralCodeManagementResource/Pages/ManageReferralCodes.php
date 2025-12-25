<?php

namespace App\Filament\Resources\ReferralCodeManagementResource\Pages;

use App\Filament\Resources\ReferralCodeManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ManageReferralCodes extends ListRecords
{
    protected static string $resource = ReferralCodeManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reserve_vanity_code')
                ->label('Reserve Vanity Code')
                ->icon('heroicon-o-star')
                ->form([
                    \Filament\Forms\Components\TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(10)
                        ->helperText('6-10 characters, no 0/O or 1/I'),
                    \Filament\Forms\Components\Select::make('user_id')
                        ->label('Reserve For User')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Reserve For Email')
                        ->email()
                        ->helperText('For future users'),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $generator = app(\App\Services\ReferralCodeGeneratorService::class);
                    
                    if ($generator->reserveVanityCode($data['code'])) {
                        \App\Models\ReservedReferralCode::create([
                            'code' => strtoupper($data['code']),
                            'reserved_for_user_id' => $data['user_id'] ?? null,
                            'reserved_for_email' => $data['email'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'reserved_by_user_id' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Vanity Code Reserved')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Code Already Exists')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}

