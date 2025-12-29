<?php

namespace App\Filament\Resources\LowStockAlertResource\Pages;

use App\Filament\Resources\LowStockAlertResource;
use Filament\Resources\Pages\ListRecords;

class ListLowStockAlerts extends ListRecords
{
    protected static string $resource = LowStockAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

