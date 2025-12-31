<?php

namespace App\Filament\Resources\CustomizationTemplateResource\Pages;

use App\Filament\Resources\CustomizationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomizationTemplate extends EditRecord
{
    protected static string $resource = CustomizationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

