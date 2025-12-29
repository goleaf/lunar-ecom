<?php

namespace App\Filament\Resources\InventoryLevelResource\Pages;

use App\Filament\Resources\InventoryLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLevel extends EditRecord
{
    protected static string $resource = InventoryLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(false),
        ];
    }
}

