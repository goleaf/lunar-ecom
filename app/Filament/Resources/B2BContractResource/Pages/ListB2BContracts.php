<?php

namespace App\Filament\Resources\B2BContractResource\Pages;

use App\Filament\Resources\B2BContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListB2BContracts extends ListRecords
{
    protected static string $resource = B2BContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

