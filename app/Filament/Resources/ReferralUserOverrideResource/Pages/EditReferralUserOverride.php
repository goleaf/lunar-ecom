<?php

namespace App\Filament\Resources\ReferralUserOverrideResource\Pages;

use App\Filament\Resources\ReferralUserOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReferralUserOverride extends EditRecord
{
    protected static string $resource = ReferralUserOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}


