<?php

namespace App\Filament\Resources\ReferralCodeManagementResource\Pages;

use App\Filament\Resources\ReferralCodeManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferralCode extends EditRecord
{
    protected static string $resource = ReferralCodeManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}


