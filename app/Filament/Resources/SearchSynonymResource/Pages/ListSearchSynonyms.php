<?php

namespace App\Filament\Resources\SearchSynonymResource\Pages;

use App\Filament\Resources\SearchSynonymResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSearchSynonyms extends ListRecords
{
    protected static string $resource = SearchSynonymResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

