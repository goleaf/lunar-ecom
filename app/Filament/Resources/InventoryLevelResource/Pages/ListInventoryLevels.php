<?php

namespace App\Filament\Resources\InventoryLevelResource\Pages;

use App\Filament\Resources\InventoryLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryLevels extends ListRecords
{
    protected static string $resource = InventoryLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(false),
        ];
    }
}

