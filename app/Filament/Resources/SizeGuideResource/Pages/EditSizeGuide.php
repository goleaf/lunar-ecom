<?php

namespace App\Filament\Resources\SizeGuideResource\Pages;

use App\Filament\Resources\SizeGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSizeGuide extends EditRecord
{
    protected static string $resource = SizeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

