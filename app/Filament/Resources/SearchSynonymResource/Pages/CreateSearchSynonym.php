<?php

namespace App\Filament\Resources\SearchSynonymResource\Pages;

use App\Filament\Resources\SearchSynonymResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateSearchSynonym extends CreateRecord
{
    protected static string $resource = SearchSynonymResource::class;

    protected function afterCreate(): void
    {
        Cache::flush();
    }
}

