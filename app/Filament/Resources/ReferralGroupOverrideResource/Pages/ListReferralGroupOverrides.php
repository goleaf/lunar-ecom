<?php

namespace App\Filament\Resources\ReferralGroupOverrideResource\Pages;

use App\Filament\Resources\ReferralGroupOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralGroupOverrides extends ListRecords
{
    protected static string $resource = ReferralGroupOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


