<?php

namespace App\Filament\Resources\ReferralCodeManagementResource\Pages;

use App\Filament\Resources\ReferralCodeManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferralCode extends ViewRecord
{
    protected static string $resource = ReferralCodeManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

