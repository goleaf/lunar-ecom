<?php

namespace App\Filament\Resources\FitFinderQuizResource\Pages;

use App\Filament\Resources\FitFinderQuizResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFitFinderQuizzes extends ListRecords
{
    protected static string $resource = FitFinderQuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

