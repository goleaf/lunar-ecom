<?php

namespace App\Filament\Resources\ReferralAttributionResource\Pages;

use App\Filament\Resources\ReferralAttributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralAttributions extends ListRecords
{
    protected static string $resource = ReferralAttributionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


