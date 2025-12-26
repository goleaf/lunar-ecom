<?php

namespace App\Filament\Resources\ReferralGroupOverrideResource\Pages;

use App\Filament\Resources\ReferralGroupOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferralGroupOverride extends EditRecord
{
    protected static string $resource = ReferralGroupOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}


