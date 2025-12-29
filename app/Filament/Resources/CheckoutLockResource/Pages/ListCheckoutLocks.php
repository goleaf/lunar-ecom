<?php

namespace App\Filament\Resources\CheckoutLockResource\Pages;

use App\Filament\Resources\CheckoutLockResource;
use Filament\Resources\Pages\ListRecords;

class ListCheckoutLocks extends ListRecords
{
    protected static string $resource = CheckoutLockResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

