<?php

namespace App\Domain\StartingNumber\Filament\Resources\StartingNumberResource\Pages;

use App\Domain\StartingNumber\Filament\Resources\StartingNumberResource;
use Filament\Resources\Pages\ListRecords;

class ListStartingNumbers extends ListRecords
{
    protected static string $resource = StartingNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
