<?php

namespace App\Filament\Resources\ReferralAttributionResource\Pages;

use App\Filament\Resources\ReferralAttributionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReferralAttribution extends CreateRecord
{
    protected static string $resource = ReferralAttributionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The DB schema requires an `attributed_at` timestamp; default it for manual admin creation.
        $data['attributed_at'] ??= now();

        return $data;
    }
}


