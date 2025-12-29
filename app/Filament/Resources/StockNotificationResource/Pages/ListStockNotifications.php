<?php

namespace App\Filament\Resources\StockNotificationResource\Pages;

use App\Filament\Resources\StockNotificationResource;
use Filament\Resources\Pages\ListRecords;

class ListStockNotifications extends ListRecords
{
    protected static string $resource = StockNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

