<?php

namespace App\Filament\Resources\SizeGuideResource\Pages;

use App\Filament\Resources\SizeGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSizeGuides extends ListRecords
{
    protected static string $resource = SizeGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

