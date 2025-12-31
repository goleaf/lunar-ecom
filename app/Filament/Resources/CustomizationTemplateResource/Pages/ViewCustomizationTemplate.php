<?php

namespace App\Filament\Resources\CustomizationTemplateResource\Pages;

use App\Filament\Resources\CustomizationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomizationTemplate extends ViewRecord
{
    protected static string $resource = CustomizationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

