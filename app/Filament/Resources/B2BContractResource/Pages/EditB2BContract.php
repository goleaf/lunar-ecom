<?php

namespace App\Filament\Resources\B2BContractResource\Pages;

use App\Filament\Resources\B2BContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditB2BContract extends EditRecord
{
    protected static string $resource = B2BContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

