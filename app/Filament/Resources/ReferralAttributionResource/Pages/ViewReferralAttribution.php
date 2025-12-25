<?php

namespace App\Filament\Resources\ReferralAttributionResource\Pages;

use App\Filament\Resources\ReferralAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReferralAttribution extends ViewRecord
{
    protected static string $resource = ReferralAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

