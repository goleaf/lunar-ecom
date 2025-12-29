<?php

namespace App\Filament\Resources\MarginAlertResource\Pages;

use App\Filament\Resources\MarginAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListMarginAlerts extends ListRecords
{
    protected static string $resource = MarginAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

