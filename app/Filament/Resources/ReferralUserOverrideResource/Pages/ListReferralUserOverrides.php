<?php

namespace App\Filament\Resources\ReferralUserOverrideResource\Pages;

use App\Filament\Resources\ReferralUserOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReferralUserOverrides extends ListRecords
{
    protected static string $resource = ReferralUserOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


