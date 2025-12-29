<?php

namespace App\Filament\Resources\PriceSimulationResource\Pages;

use App\Filament\Resources\PriceSimulationResource;
use Filament\Resources\Pages\ListRecords;

class ListPriceSimulations extends ListRecords
{
    protected static string $resource = PriceSimulationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

