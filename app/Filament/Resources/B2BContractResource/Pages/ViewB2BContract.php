<?php

namespace App\Filament\Resources\B2BContractResource\Pages;

use App\Filament\Resources\B2BContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewB2BContract extends ViewRecord
{
    protected static string $resource = B2BContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}


