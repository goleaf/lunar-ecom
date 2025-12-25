<?php

namespace App\Filament\Resources\FitFeedbackResource\Pages;

use App\Filament\Resources\FitFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFitFeedbacks extends ListRecords
{
    protected static string $resource = FitFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

