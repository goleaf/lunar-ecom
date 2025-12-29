<?php

namespace App\Filament\Resources\SearchAnalyticResource\Pages;

use App\Filament\Resources\SearchAnalyticResource;
use Filament\Resources\Pages\ListRecords;

class ListSearchAnalytics extends ListRecords
{
    protected static string $resource = SearchAnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

